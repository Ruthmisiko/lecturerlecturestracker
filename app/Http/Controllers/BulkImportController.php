<?php

namespace App\Http\Controllers;

use App\Exports\Bulk\BulkTemplateExport;
use App\Exports\Bulk\InstructionSheet;
use App\Exports\Bulk\TemplateSheet;
use App\Imports\RowsImport;
use App\Models\Classs;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

/** Bulk upload of lecturers, units and classes from an Excel template. */
class BulkImportController extends Controller
{
    private const MAX_ROWS = 1000;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            // Classes need the class permissions; lecturers and units the lecturer ones.
            $area = str_contains(strtolower($request->route()->getActionMethod()), 'class') ? 'class' : 'lecturer';
            abort_unless($user && ($user->isSuperAdmin() || $user->can("{$area}.create") || $user->can("{$area}.manage")), 403);
            return $next($request);
        });
    }

    // ---------------------------------------------------------------- templates

    public function lecturerTemplate()
    {
        $pay = Auth::user()->isSuperAdmin();

        $headings = ['name', 'email', 'phone', 'id_number', 'kra_pin', 'specialization', 'department', 'units'];
        $example  = ['EXAMPLE - delete this row', 'jane@example.com', '0712345678', '12345678', 'A001234567Z', 'Anatomy', $this->departmentNames()[0] ?? 'Department name', 'Unit one; Unit two'];
        $columns  = [
            ['name', 'Yes', 'Full name of the lecturer.'],
            ['email', 'Yes', 'Must be unique. A lecturer whose email already exists is skipped.'],
            ['phone', 'No', 'Phone number.'],
            ['id_number', 'Yes', 'National ID number (digits only). Must be unique.'],
            ['kra_pin', 'No', 'KRA PIN.'],
            ['specialization', 'No', 'Area of specialization.'],
            ['department', 'No', 'Pick from the dropdown. Must match an existing department exactly.'],
            ['units', 'No', 'Units this lecturer teaches, separated by ; (name or unit code). Unknown units are ignored with a warning.'],
        ];

        if ($pay) {
            array_push($headings, 'rate_per_hour', 'tax_rate', 'housing_levy_rate', 'account_number', 'bank_name', 'bank_branch');
            array_push($example, '1000', '30', '1.5', '1234567890', 'KCB', 'Siaya');
            array_push($columns,
                ['rate_per_hour', 'No', 'Pay per hour. Leave blank for ' . Lecturer::DEFAULT_RATE_PER_HOUR . '.'],
                ['tax_rate', 'No', 'Tax %. Leave blank for ' . Lecturer::DEFAULT_TAX_RATE . '.'],
                ['housing_levy_rate', 'No', 'Housing levy %. Leave blank for ' . Lecturer::DEFAULT_HOUSING_LEVY_RATE . '.'],
                ['account_number', 'No', 'Bank account number (used on payment schedules).'],
                ['bank_name', 'No', 'Bank name.'],
                ['bank_branch', 'No', 'Bank branch.'],
            );
        }

        return $this->download('lecturers_template.xlsx', 'Bulk upload of lecturers', $headings, $example, $columns,
            ['department' => $this->departmentNames()],
            ['phone', 'id_number', 'kra_pin', 'account_number'],
            [
                'Fill in from row 3. Row 2 is only an example (it is ignored while its name starts with "EXAMPLE").',
                'Do not rename or reorder the column headings.',
                'Rows with a problem are skipped and listed after the upload; the good rows are still added.',
                'Up to ' . self::MAX_ROWS . ' rows per file.',
            ]);
    }

    public function unitTemplate()
    {
        $max = Auth::user()->isSuperAdmin();

        $headings = ['name', 'unit_code', 'department'];
        $example  = ['EXAMPLE - delete this row', 'ANA101', $this->departmentNames()[0] ?? 'Department name'];
        $columns  = [
            ['name', 'Yes', 'Unit name.'],
            ['unit_code', 'No', 'Unit code, e.g. ANA101.'],
            ['department', 'No', 'Pick from the dropdown. Must match an existing department exactly.'],
        ];

        if ($max) {
            $headings[] = 'max_hours';
            $example[]  = '30';
            $columns[]  = ['max_hours', 'No', 'Most hours a lecturer is paid for in this unit. Leave blank for no limit.'];
        }

        return $this->download('units_template.xlsx', 'Bulk upload of units', $headings, $example, $columns,
            ['department' => $this->departmentNames()],
            ['unit_code'],
            [
                'Fill in from row 3. Row 2 is only an example (it is ignored while its name starts with "EXAMPLE").',
                'Do not rename or reorder the column headings.',
                'A unit with the same name in the same department that already exists is skipped.',
                'Up to ' . self::MAX_ROWS . ' rows per file.',
            ]);
    }

    public function classTemplate()
    {
        return $this->download('classes_template.xlsx', 'Bulk upload of classes',
            ['name', 'code', 'description', 'units'],
            ['EXAMPLE - delete this row', 'KRCHN-SEP26', 'Kenya Registered Community Health Nursing, September 2026 intake', 'Unit one; Unit two'],
            [
                ['name', 'Yes', 'Class name. A class whose name already exists is skipped.'],
                ['code', 'No', 'Class code.'],
                ['description', 'No', 'Short description.'],
                ['units', 'No', 'Units this class takes, separated by ; (name or unit code). Unknown units are ignored with a warning.'],
            ],
            [],
            ['code'],
            [
                'Fill in from row 3. Row 2 is only an example (it is ignored while its name starts with "EXAMPLE").',
                'Do not rename or reorder the column headings.',
                'Rows with a problem are skipped and listed after the upload; the good rows are still added.',
                'Up to ' . self::MAX_ROWS . ' rows per file.',
            ]);
    }

    // ------------------------------------------------------------------ imports

    public function importLecturers(Request $request)
    {
        $rows = $this->readRows($request);
        if (is_string($rows)) {
            return back()->withErrors(['file' => $rows]);
        }

        $user    = Auth::user();
        $pay     = $user->isSuperAdmin();
        $ownerId = $user->ownerId();
        $deptIds = $user->scopedDepartmentIds();
        $units   = Unit::get(['id', 'name', 'unitCode']);
        $report  = $this->newReport('Lecturers');

        foreach ($rows as $i => $row) {
            $n = $i + 2; // spreadsheet row number
            $name = $this->text($row['name'] ?? null);
            if ($this->skip($row, $name)) {
                continue;
            }

            $data = [
                'name'           => $name,
                'email'          => strtolower($this->text($row['email'] ?? null)),
                'phone'          => $this->text($row['phone'] ?? null),
                'id_number'      => $this->number($row['id_number'] ?? null),
                'kra_pin'        => strtoupper($this->text($row['kra_pin'] ?? null)),
                'specialization' => $this->text($row['specialization'] ?? null),
            ];
            if ($pay) {
                foreach (['rate_per_hour', 'tax_rate', 'housing_levy_rate'] as $f) {
                    $data[$f] = $this->number($row[$f] ?? null);
                }
                foreach (['account_number', 'bank_name', 'bank_branch'] as $f) {
                    $data[$f] = $this->text($row[$f] ?? null);
                }
            }

            $validator = Validator::make($data, [
                'name'              => 'required|string|max:255',
                'email'             => 'required|email|max:255',
                'phone'             => 'nullable|string|max:50',
                'id_number'         => 'required|integer|min:1|max:2147483647',
                'kra_pin'           => 'nullable|string|max:50',
                'specialization'    => 'nullable|string|max:255',
                'rate_per_hour'     => 'nullable|numeric|min:0',
                'tax_rate'          => 'nullable|numeric|min:0|max:100',
                'housing_levy_rate' => 'nullable|numeric|min:0|max:100',
            ], [
                'id_number.required' => 'ID number is required.',
                'id_number.integer'  => 'ID number must be digits only.',
            ]);

            $problems = $validator->errors()->all();

            // Email and ID number are unique across the whole system.
            if (!$problems) {
                if (Lecturer::withoutGlobalScopes()->where('email', $data['email'])->exists()) {
                    $problems[] = 'a lecturer with this email already exists';
                }
                if (Lecturer::withoutGlobalScopes()->where('id_number', $data['id_number'])->exists()) {
                    $problems[] = 'a lecturer with this ID number already exists';
                }
            }

            $department = null;
            $deptName   = $this->text($row['department'] ?? null);
            if (!$problems && $deptName !== '') {
                $department = $this->findDepartment($deptName);
                if (!$department) {
                    $problems[] = "department \"{$deptName}\" not found";
                } elseif ($deptIds && !in_array($department->id, $deptIds)) {
                    $problems[] = "you are not allowed to add lecturers to \"{$deptName}\"";
                }
            } elseif (!$problems && $deptIds && count($deptIds) === 1) {
                $department = Department::find($deptIds[0]); // restricted users: their own department
            }

            if ($problems) {
                $this->skipRow($report, $n, $name, $problems);
                continue;
            }

            $unitIds = [];
            $warnings = [];
            foreach (array_filter(array_map('trim', preg_split('/[;,\n]+/', $this->text($row['units'] ?? null)))) as $ref) {
                $match = $units->first(fn ($u) => strcasecmp($u->name, $ref) === 0 || ($u->unitCode && strcasecmp($u->unitCode, $ref) === 0));
                $match ? $unitIds[] = $match->id : $warnings[] = "unit \"{$ref}\" not found";
            }

            DB::transaction(function () use ($data, $ownerId, $department, $unitIds, $pay) {
                $attrs = [
                    'user_id'        => $ownerId,
                    'department_id'  => $department?->id,
                    'name'           => $data['name'],
                    'email'          => $data['email'],
                    'phone'          => $data['phone'] ?: null,
                    'id_number'      => $data['id_number'],
                    'kra_pin'        => $data['kra_pin'] ?: null,
                    'specialization' => $data['specialization'] ?: null,
                ];

                if ($pay) {
                    $attrs += [
                        'rate_per_hour'     => $data['rate_per_hour'] ?? Lecturer::DEFAULT_RATE_PER_HOUR,
                        'tax_rate'          => $data['tax_rate'] ?? Lecturer::DEFAULT_TAX_RATE,
                        'housing_levy_rate' => $data['housing_levy_rate'] ?? Lecturer::DEFAULT_HOUSING_LEVY_RATE,
                        'account_number'    => $data['account_number'] ?: null,
                        'bank_name'         => $data['bank_name'] ?: null,
                        'bank_branch'       => $data['bank_branch'] ?: null,
                    ];
                }

                $lecturer = Lecturer::create($attrs);
                if ($unitIds) {
                    $lecturer->units()->sync($unitIds);
                }
            });

            $report['created']++;
            foreach ($warnings as $w) {
                $report['warnings'][] = ['row' => $n, 'name' => $name, 'message' => $w];
            }
        }

        return $this->result($report, route('lecturers.index'), 'Back to Lecturers');
    }

    public function importUnits(Request $request)
    {
        $rows = $this->readRows($request);
        if (is_string($rows)) {
            return back()->withErrors(['file' => $rows]);
        }

        $user    = Auth::user();
        $maxCol  = $user->isSuperAdmin();
        $ownerId = $user->ownerId();
        $deptIds = $user->scopedDepartmentIds();
        $report  = $this->newReport('Units');

        foreach ($rows as $i => $row) {
            $n    = $i + 2;
            $name = $this->text($row['name'] ?? null);
            if ($this->skip($row, $name)) {
                continue;
            }

            $data = [
                'name'      => $name,
                'unit_code' => $this->text($row['unit_code'] ?? $row['unitcode'] ?? null),
            ];
            if ($maxCol) {
                $data['max_hours'] = $this->number($row['max_hours'] ?? null);
            }

            $problems = Validator::make($data, [
                'name'      => 'required|string|max:255',
                'unit_code' => 'nullable|string|max:255',
                'max_hours' => 'nullable|numeric|min:0',
            ])->errors()->all();

            $department = null;
            $deptName   = $this->text($row['department'] ?? null);
            if (!$problems && $deptName !== '') {
                $department = $this->findDepartment($deptName);
                if (!$department) {
                    $problems[] = "department \"{$deptName}\" not found";
                } elseif ($deptIds && !in_array($department->id, $deptIds)) {
                    $problems[] = "you are not allowed to add units to \"{$deptName}\"";
                }
            } elseif (!$problems && $deptIds && count($deptIds) === 1) {
                $department = Department::find($deptIds[0]);
            }

            if (!$problems) {
                $exists = Unit::whereRaw('LOWER(name) = ?', [mb_strtolower($data['name'])])
                    ->where('department_id', $department?->id)->exists();
                if ($exists) {
                    $problems[] = 'a unit with this name already exists in that department';
                }
            }

            if ($problems) {
                $this->skipRow($report, $n, $name, $problems);
                continue;
            }

            Unit::create([
                'user_id'       => $ownerId,
                'department_id' => $department?->id,
                'name'          => $data['name'],
                'unitCode'      => $data['unit_code'] ?: null,
            ] + ($maxCol ? ['max_hours' => $data['max_hours']] : []));

            $report['created']++;
        }

        return $this->result($report, route('units.index'), 'Back to Units');
    }

    public function importClasses(Request $request)
    {
        $rows = $this->readRows($request);
        if (is_string($rows)) {
            return back()->withErrors(['file' => $rows]);
        }

        $ownerId = Auth::user()->ownerId();
        $units   = Unit::where('user_id', $ownerId)->get(['id', 'name', 'unitCode']);
        $report  = $this->newReport('Classes');

        foreach ($rows as $i => $row) {
            $n    = $i + 2;
            $name = $this->text($row['name'] ?? null);
            if ($this->skip($row, $name)) {
                continue;
            }

            $data = [
                'name'        => $name,
                'code'        => $this->text($row['code'] ?? null),
                'description' => $this->text($row['description'] ?? null),
            ];

            $problems = Validator::make($data, [
                'name'        => 'required|string|max:255',
                'code'        => 'nullable|string|max:255',
                'description' => 'nullable|string|max:255',
            ])->errors()->all();

            if (!$problems && Classs::where('user_id', $ownerId)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                $problems[] = 'a class with this name already exists';
            }

            if ($problems) {
                $this->skipRow($report, $n, $name, $problems);
                continue;
            }

            $unitIds  = [];
            $warnings = [];
            foreach (array_filter(array_map('trim', preg_split('/[;,\n]+/', $this->text($row['units'] ?? null)))) as $ref) {
                $match = $units->first(fn ($u) => strcasecmp($u->name, $ref) === 0 || ($u->unitCode && strcasecmp($u->unitCode, $ref) === 0));
                $match ? $unitIds[] = $match->id : $warnings[] = "unit \"{$ref}\" not found";
            }

            DB::transaction(function () use ($data, $ownerId, $unitIds) {
                $class = Classs::create([
                    'user_id'     => $ownerId,
                    'name'        => $data['name'],
                    'code'        => $data['code'] ?: null,
                    'description' => $data['description'] ?: null,
                ]);
                if ($unitIds) {
                    $class->units()->sync($unitIds);
                }
            });

            $report['created']++;
            foreach ($warnings as $w) {
                $report['warnings'][] = ['row' => $n, 'name' => $name, 'message' => $w];
            }
        }

        return $this->result($report, route('classses.index'), 'Back to Classes');
    }

    // ------------------------------------------------------------------ helpers

    private function download(string $file, string $heading, array $headings, array $example, array $columns, array $dropdowns, array $textColumns, array $tips)
    {
        return Excel::download(new BulkTemplateExport(
            new TemplateSheet($headings, $example, $dropdowns, $textColumns),
            new InstructionSheet($heading, $columns, $tips),
        ), $file);
    }

    /** @return array|string rows, or an error message */
    private function readRows(Request $request): array|string
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:5120'], [
            'file.required' => 'Choose the filled-in template to upload.',
            'file.mimes'    => 'Upload the Excel template (.xlsx, .xls or .csv).',
            'file.max'      => 'The file must be 5 MB or smaller.',
        ]);

        try {
            $rows = Excel::toArray(new RowsImport, $request->file('file'))[0] ?? [];
        } catch (\Throwable $e) {
            report($e);
            return 'That file could not be read. Use the template you downloaded, saved as .xlsx.';
        }

        $rows = array_values(array_filter($rows, fn ($r) => collect($r)->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->isNotEmpty()));

        if (!$rows) {
            return 'The file has no rows to import. Fill in the template from row 3.';
        }
        if (count($rows) > self::MAX_ROWS) {
            return 'The file has more than ' . self::MAX_ROWS . ' rows. Split it into smaller files.';
        }

        return $rows;
    }

    private function departmentNames(): array
    {
        $deptIds = Auth::user()->scopedDepartmentIds();

        return Department::when($deptIds, fn ($q) => $q->whereIn('id', $deptIds))->orderBy('name')->pluck('name')->all();
    }

    private function findDepartment(string $name): ?Department
    {
        return Department::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
    }

    private function text($value): string
    {
        if ($value === null) {
            return '';
        }

        // Excel may hand over 12345678 as a float; keep whole numbers clean.
        if (is_float($value) && floor($value) == $value) {
            $value = (string) (int) $value;
        }

        return trim((string) $value);
    }

    private function number($value)
    {
        $text = $this->text($value);

        return $text === '' ? null : (is_numeric($text) ? $text + 0 : $text);
    }

    /** True for the grey example row. */
    private function skip(array $row, string $name): bool
    {
        return $name === '' && collect($row)->filter(fn ($v) => $v !== null && trim((string) $v) !== '')->isEmpty()
            || stripos($name, 'EXAMPLE') === 0;
    }

    private function newReport(string $what): array
    {
        return ['what' => $what, 'created' => 0, 'skipped' => [], 'warnings' => []];
    }

    private function skipRow(array &$report, int $row, string $name, array $problems): void
    {
        $report['skipped'][] = ['row' => $row, 'name' => $name !== '' ? $name : '(no name)', 'message' => implode('; ', $problems)];
    }

    private function result(array $report, string $backUrl, string $backLabel)
    {
        return view('bulk.result', compact('report', 'backUrl', 'backLabel'));
    }
}
