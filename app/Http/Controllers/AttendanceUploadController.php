<?php

namespace App\Http\Controllers;

use Flash;
use App\Models\Classs;
use App\Models\Department;
use App\Models\LectureAdministered;
use App\Models\Lecturer;
use App\Models\Semester;
use App\Models\Unit;
use App\Services\AttendanceSheetReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Upload photos/PDFs of attendance sheets → Gemini reads the dates and times →
 * the user reviews/edits the rows → lecture records are created.
 */
class AttendanceUploadController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            abort_unless($user && ($user->can('lecture.create') || $user->isSuperAdmin()), 403);
            return $next($request);
        });
    }

    public function create()
    {
        return view('lecture_administereds.attendance_upload', $this->lists() + [
            'configured' => AttendanceSheetReader::configured(),
        ]);
    }

    public function read(Request $request, AttendanceSheetReader $reader)
    {
        $request->validate($this->selectionRules() + [
            'files'   => 'required|array|min:1|max:10',
            'files.*' => 'file|mimes:jpg,jpeg,png,webp,heic,heif,pdf|max:10240',
        ], [
            'files.required' => 'Choose at least one photo or PDF of the attendance sheet.',
            'files.max'      => 'Upload at most 10 files at a time.',
            'files.*.mimes'  => 'Only photos (JPG, PNG, WEBP, HEIC) or PDF files can be read.',
            'files.*.max'    => 'Each file must be 10 MB or smaller.',
        ]);

        $selection = $this->selection($request);
        $rows      = [];
        $readErrors = [];

        foreach ($request->file('files') as $file) {
            try {
                foreach ($reader->read($file) as $row) {
                    $rows[] = $row + ['file' => $file->getClientOriginalName()];
                }
            } catch (\Throwable $e) {
                report($e);
                $readErrors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        $rows = $this->flag($rows, $selection);

        return view('lecture_administereds.attendance_review', compact('rows', 'readErrors', 'selection'));
    }

    public function store(Request $request)
    {
        $request->validate($this->selectionRules() + [
            'rows'              => 'required|array|min:1',
            'rows.*.include'    => 'nullable|boolean',
            'rows.*.date'       => 'nullable|date',
            'rows.*.start_time' => 'nullable|date_format:H:i',
            'rows.*.end_time'   => 'nullable|date_format:H:i',
        ]);

        $selection = $this->selection($request);
        $chosen    = collect($request->input('rows'))->filter(fn ($r) => !empty($r['include']));

        foreach ($chosen as $r) {
            if (empty($r['date']) || empty($r['start_time']) || empty($r['end_time'])) {
                return back()->withInput()->withErrors(['rows' => 'Every ticked row needs a date, a start time and an end time.']);
            }
            if ($r['end_time'] <= $r['start_time']) {
                return back()->withInput()->withErrors(['rows' => "On {$r['date']} the end time must be after the start time."]);
            }
        }

        $created = 0;

        DB::transaction(function () use ($chosen, $selection, &$created) {
            foreach ($chosen as $r) {
                LectureAdministered::create([
                    'user_id'       => Auth::user()->ownerId(),
                    'lecturer_id'   => $selection['lecturer']->id,
                    'department_id' => $selection['department']?->id,
                    'unit_id'       => $selection['unit']?->id,
                    'classs_id'     => $selection['class']->id,
                    'semester_id'   => $selection['semester']?->id,
                    'lecture_date'  => $r['date'],
                    'start_time'    => $r['start_time'],
                    'end_time'      => $r['end_time'],
                ]);
                $created++;
            }
        });

        Flash::success("{$created} lecture record(s) created for {$selection['lecturer']->name}.");

        return redirect()->route('lecture-administereds.index', ['lecturer_id' => $selection['lecturer']->id]);
    }

    // ---------------------------------------------------------------------

    private function lists(): array
    {
        $user    = Auth::user();
        $ownerId = $user->ownerId();
        $deptIds = $user->scopedDepartmentIds();

        return [
            'lecturers'   => Lecturer::where('user_id', $ownerId)
                ->when($deptIds, fn ($q) => $q->whereIn('department_id', $deptIds))
                ->orderBy('name')->get(['id', 'name', 'department_id']),
            'departments' => Department::where('user_id', $ownerId)
                ->when($deptIds, fn ($q) => $q->whereIn('id', $deptIds))
                ->orderBy('name')->get(['id', 'name']),
            'units'       => Unit::where('user_id', $ownerId)
                ->when($deptIds, fn ($q) => $q->whereIn('department_id', $deptIds))
                ->orderBy('name')->get(['id', 'name', 'unitCode', 'department_id']),
            'classes'     => Classs::where('user_id', $ownerId)->orderBy('name')->get(['id', 'name']),
            'semesters'   => Semester::orderByDesc('is_current')->orderByDesc('start_date')->orderByDesc('id')->get(),
        ];
    }

    private function selectionRules(): array
    {
        return [
            'lecturer_id'   => 'required|integer',
            'department_id' => 'nullable|integer',
            'unit_id'       => 'nullable|integer',
            'classs_id'     => 'required|integer',
            'semester_id'   => 'nullable|integer',
        ];
    }

    /** The chosen records, looked up within the current institution (so other institutions' IDs fail). */
    private function selection(Request $request): array
    {
        return [
            'lecturer'   => Lecturer::findOrFail($request->lecturer_id),
            'department' => $request->filled('department_id') ? Department::findOrFail($request->department_id) : null,
            'unit'       => $request->filled('unit_id') ? Unit::findOrFail($request->unit_id) : null,
            'class'      => Classs::findOrFail($request->classs_id),
            'semester'   => $request->filled('semester_id') ? Semester::findOrFail($request->semester_id) : Semester::current(),
        ];
    }

    /** Add review flags: unreadable, low confidence, already recorded, repeated on the sheets, outside the semester. */
    private function flag(array $rows, array $selection): array
    {
        $seen     = [];
        $semester = $selection['semester'];

        foreach ($rows as &$row) {
            $notes = [];

            if ($row['problem']) {
                $notes[] = $row['problem'];
            } elseif ($row['low']) {
                $notes[] = 'Hard to read — please check';
            }

            if ($row['date'] && $row['start_time']) {
                $key = $row['date'] . ' ' . $row['start_time'];

                if ($this->alreadyRecorded($selection['lecturer']->id, $row['date'], $row['start_time'])) {
                    $notes[] = 'Already recorded (will be added again if saved)';
                } elseif (isset($seen[$key])) {
                    $notes[] = 'Appears twice in the uploads';
                }
                $seen[$key] = true;

                if ($semester && (($semester->start_date && $row['date'] < $semester->start_date->format('Y-m-d'))
                    || ($semester->end_date && $row['date'] > $semester->end_date->format('Y-m-d')))) {
                    $notes[] = 'Outside ' . $semester->name . ' dates';
                }
            }

            $row['notes']   = $notes;
            $row['include'] = !$row['problem'];
        }

        return collect($rows)->sortBy(fn ($r) => ($r['date'] ?? '9999') . ($r['start_time'] ?? ''))->values()->all();
    }

    private function alreadyRecorded(int $lecturerId, string $date, string $start): bool
    {
        return LectureAdministered::where('lecturer_id', $lecturerId)
            ->whereDate('lecture_date', $date)
            ->where('start_time', 'like', $start . '%')
            ->exists();
    }
}
