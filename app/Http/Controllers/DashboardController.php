<?php

namespace App\Http\Controllers;

use PDF;
use App\Exports\CollectionExport;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\Unit;
use App\Services\LecturerPayroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Dashboard is superuser-only; send everyone else to their working page.
        if (!$user->hasAccess('dashboard.view')) {
            return redirect()->route($user->can('lecture.manage') ? 'lecture-administereds.index' : 'lecturers.index');
        }

        [$from, $to] = $this->period($request);
        $statuses    = $this->statuses($request);
        $ownerId     = $user->ownerId();
        $deptIds     = $user->scopedDepartmentIds();

        $rows   = (new LecturerPayroll($user, $from, $to, $statuses))->rows();
        $totals = LecturerPayroll::totals($rows);

        $stats = [
            'lecturers'   => Lecturer::where('user_id', $ownerId)
                ->when($deptIds, fn ($q) => $q->whereIn('department_id', $deptIds))->count(),
            'units'       => Unit::where('user_id', $ownerId)
                ->when($deptIds, fn ($q) => $q->whereIn('department_id', $deptIds))->count(),
            'departments' => Department::where('user_id', $ownerId)
                ->when($deptIds, fn ($q) => $q->whereIn('id', $deptIds))->count(),
            'hours'       => $totals->hours,
        ];

        $missingRate = $rows->filter(fn ($r) => $r->hours > 0 && $r->rate <= 0);

        $departmentSummary = $rows->groupBy('department')
            ->map(fn ($g, $name) => (object) [
                'name'  => $name,
                'hours' => round($g->sum('hours'), LecturerPayroll::PRECISION),
                'net'   => round($g->sum('net'), LecturerPayroll::PRECISION),
            ])
            ->sortByDesc('hours')
            ->values();

        // Per-department figures for the reports charts (same period and payable statuses as the dashboard).
        $lecturerCounts = Lecturer::where('user_id', $ownerId)
            ->when($deptIds, fn ($q) => $q->whereIn('department_id', $deptIds))
            ->selectRaw('department_id, count(*) as c')->groupBy('department_id')->pluck('c', 'department_id');
        $unitCounts = Unit::where('user_id', $ownerId)
            ->when($deptIds, fn ($q) => $q->whereIn('department_id', $deptIds))
            ->selectRaw('department_id, count(*) as c')->groupBy('department_id')->pluck('c', 'department_id');
        $byDept = $rows->groupBy('department_id');

        $charts = Department::where('user_id', $ownerId)
            ->when($deptIds, fn ($q) => $q->whereIn('id', $deptIds))
            ->orderBy('name')
            ->get(['id', 'name', 'approved_hours'])
            ->map(fn ($d) => [
                'name'       => $d->name,
                'lecturers'  => (int) ($lecturerCounts[$d->id] ?? 0),
                'units'      => (int) ($unitCounts[$d->id] ?? 0),
                'hours'      => round($byDept->get($d->id, collect())->sum('hours'), 2),
                'net'        => round($byDept->get($d->id, collect())->sum('net'), 2),
                'approved'   => $d->approved_hours !== null ? (float) $d->approved_hours : null,
            ])
            ->values();

        $emailConfigs = \App\Models\EmailConfiguration::orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'from_address', 'is_default']);
        $emailTemplates = \App\Models\EmailTemplate::orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'subject', 'body', 'is_default']);
        $lastEmailed  = \App\Models\PayslipEmail::whereIn('lecturer_id', $rows->pluck('id'))
            ->latest()->get(['lecturer_id', 'to', 'created_at'])->unique('lecturer_id')
            ->mapWithKeys(fn ($e) => [$e->lecturer_id => ['to' => $e->to, 'at' => $e->created_at->format('d M Y, H:i')]]);

        return view('dashboard', compact('rows', 'totals', 'stats', 'missingRate', 'departmentSummary', 'from', 'to', 'statuses', 'charts', 'emailConfigs', 'emailTemplates', 'lastEmailed'));
    }

    public function report(Request $request)
    {
        $this->authorizeAccess('payment-report.view');

        [$from, $to] = $this->period($request);
        $statuses     = $this->statuses($request);
        $overrides    = $this->rowStatuses($request);
        $departmentId = $request->integer('department_id') ?: null;

        $rows = $this->reportRows($from, $to, $statuses, $overrides)
            // Only lecturers who taught in the period (or whose row was adjusted).
            ->filter(fn ($r) => $r->total_sessions > 0 || array_key_exists($r->id, $overrides))
            ->when($departmentId, fn ($c) => $c->where('department_id', $departmentId))
            ->values();

        // PV / voucher numbers typed on the report (not stored; carried into the downloads).
        $pv = collect((array) $request->input('pv', []))
            ->filter(fn ($v, $id) => ctype_digit((string) $id) && is_string($v) && trim($v) !== '')
            ->map(fn ($v) => mb_substr(trim($v), 0, 50))
            ->all();
        foreach ($rows as $r) {
            $r->pv_no = $pv[$r->id] ?? '';
        }

        $sections = $rows->groupBy('department')
            ->map(fn ($group, $name) => (object) [
                'name'   => $name,
                'rows'   => $group->values(),
                'totals' => LecturerPayroll::totals($group),
            ])
            ->sortBy('name')
            ->values();

        $totals      = LecturerPayroll::totals($rows);
        $period      = $this->periodLabel($from, $to);
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $department  = $departmentId ? $departments->firstWhere('id', $departmentId) : null;

        if ($request->get('format') === 'excel') {
            return $this->excel($sections, $totals, $period);
        }

        if ($request->get('format') === 'pdf') {
            return PDF::loadView('dashboard.report_pdf', compact('sections', 'totals', 'period', 'statuses', 'department'))
                ->setPaper('a4', 'landscape')
                ->download('lecturer_payment_report.pdf');
        }

        return view('dashboard.report', compact('sections', 'totals', 'from', 'to', 'statuses', 'departments', 'department', 'overrides', 'pv'));
    }

    /** One lecturer's figures for the chosen payable statuses (used when a row's dropdown changes). */
    public function reportRow(Request $request)
    {
        $this->authorizeAccess('payment-report.view');

        [$from, $to] = $this->period($request);
        $row = (new LecturerPayroll(Auth::user(), $from, $to, $this->statuses($request)))->rows()
            ->firstWhere('id', $request->integer('lecturer'));

        abort_unless($row, 404);

        return response()->json($row);
    }

    /**
     * Payroll rows using the report's statuses, with individual lecturers recalculated
     * for any per-row statuses chosen on the report ("rs" = [lecturerId => "ok,own_clash" | "all"]).
     */
    protected function reportRows(?string $from, ?string $to, ?array $statuses, array $overrides)
    {
        $user = Auth::user();
        $rows = (new LecturerPayroll($user, $from, $to, $statuses))->rows()->keyBy('id');

        foreach ($rows as $row) {
            $row->statuses = $statuses;
        }

        // One payroll run per distinct status set chosen on rows.
        foreach (collect($overrides)->groupBy(fn ($s) => $s ? implode(',', $s) : 'all', true) as $group) {
            $set     = $group->first();
            $special = (new LecturerPayroll($user, $from, $to, $set))->rows()->keyBy('id');

            foreach ($group->keys() as $lecturerId) {
                if ($special->has($lecturerId)) {
                    $row = $special->get($lecturerId);
                    $row->statuses = $set;
                    $rows->put($lecturerId, $row);
                }
            }
        }

        return $rows->sortBy('name')->values();
    }

    /** Per-row payable statuses from the query string: lecturer id => statuses (null = all). */
    protected function rowStatuses(Request $request): array
    {
        $out = [];
        foreach ((array) $request->input('rs', []) as $id => $value) {
            if (ctype_digit((string) $id)) {
                $out[(int) $id] = $value === 'all' ? null : LecturerPayroll::normaliseStatuses(explode(',', (string) $value));
            }
        }

        return $out;
    }

    public function payslip(Request $request, $lecturerId)
    {
        $this->authorizeAccess('dashboard.view', 'payment-report.view');

        [$from, $to] = $this->period($request);
        [$pdf, $lecturer] = $this->payslipPdf($lecturerId, $from, $to, $this->statuses($request));

        return $pdf->download($this->payslipFileName($lecturer));
    }

    /** Emails a lecturer's payslip PDF (same period/statuses as on screen) through a chosen email account. */
    public function emailPayslip(Request $request, $lecturerId)
    {
        $this->authorizeAccess('payslip.email');

        $data = $request->validate([
            'email_configuration_id' => 'required|integer',
            'to'                     => 'required|email|max:190',
            'subject'                => 'required|string|max:200',
            'body'                   => 'required|string|max:10000',
            'save_email'             => 'nullable|boolean',
        ], [
            'email_configuration_id.required' => 'Add an email account under Email Settings first.',
            'to.email'                        => 'Enter a valid email address for the lecturer.',
        ]);

        $config = \App\Models\EmailConfiguration::find($data['email_configuration_id']);
        if (!$config) {
            return response()->json(['message' => 'That email account no longer exists.'], 422);
        }

        [$from, $to] = $this->period($request);
        [$pdf, $lecturer] = $this->payslipPdf($lecturerId, $from, $to, $this->statuses($request));

        try {
            $config->mailer()->to($data['to'])->send(new \App\Mail\PayslipMail(
                $data['subject'], $data['body'], $pdf->output(), $this->payslipFileName($lecturer),
                $config->from_address, $config->from_name,
            ));
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => 'Sending failed: ' . $e->getMessage()], 422);
        }

        if ($request->boolean('save_email') && $lecturer->email !== $data['to']) {
            $taken = Lecturer::withoutGlobalScopes()->where('email', $data['to'])->where('id', '!=', $lecturer->id)->exists();
            if (!$taken) {
                $lecturer->update(['email' => $data['to']]);
            }
        }

        \App\Models\PayslipEmail::create([
            'lecturer_id'            => $lecturer->id,
            'email_configuration_id' => $config->id,
            'to'                     => $data['to'],
            'subject'                => $data['subject'],
            'period_from'            => $from,
            'period_to'              => $to,
            'user_id'                => Auth::id(),
        ]);

        return response()->json(['message' => "Payslip sent to {$data['to']}.", 'sent_at' => now()->format('d M Y, H:i')]);
    }

    /** [Pdf, Lecturer] for a lecturer's payslip. */
    protected function payslipPdf($lecturerId, ?string $from, ?string $to, ?array $statuses): array
    {
        $lecturer = Lecturer::with('department', 'institution')->findOrFail($lecturerId);
        $row      = (new LecturerPayroll(Auth::user(), $from, $to, $statuses))->rows()->firstWhere('id', $lecturer->id);

        abort_unless($row, 404);

        return [$this->payslipPdfFor($lecturer, $row, $from, $to, $statuses), $lecturer];
    }

    /** Payslip PDF from an already-calculated payroll row (lets bulk sending calculate once). */
    protected function payslipPdfFor(Lecturer $lecturer, object $row, ?string $from, ?string $to, ?array $statuses)
    {
        $period = $this->periodLabel($from, $to);
        $slipNo = 'PS-' . str_pad($lecturer->id, 4, '0', STR_PAD_LEFT) . '-' . ($to ? str_replace('-', '', substr($to, 0, 7)) : now()->format('Ym'));

        return PDF::loadView('dashboard.payslip_pdf', compact('lecturer', 'row', 'period', 'slipNo', 'statuses', 'from', 'to'))
            ->setPaper([0, 0, 297.64, 595.28]); // half-width A4: 105 × 210 mm
    }

    protected function payslipFileName(Lecturer $lecturer): string
    {
        return 'payslip_' . str($lecturer->name)->slug('_') . '.pdf';
    }

    protected function excel($sections, $totals, string $period)
    {
        return Excel::download(
            new \App\Exports\PaymentReportExport($sections, $totals, \App\Support\Theme::current()),
            'lecturer_payment_report.xlsx'
        );
    }

    /** Optional date range from the request (Y-m-d), validated loosely. */
    protected function period(Request $request): array
    {
        $valid = fn ($v) => $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;

        return [$valid($request->get('from')), $valid($request->get('to'))];
    }

    /** Clash statuses whose hours are payable; null = all statuses. */
    protected function statuses(Request $request): ?array
    {
        return LecturerPayroll::normaliseStatuses($request->input('statuses'));
    }

    public static function statusLabel(?array $statuses): string
    {
        return $statuses
            ? collect($statuses)->map(fn ($s) => LecturerPayroll::STATUSES[$s])->join(', ')
            : 'All statuses';
    }

    protected function periodLabel(?string $from, ?string $to): string
    {
        if (!$from && !$to) return 'All time';
        return ($from ?: 'Start') . ' to ' . ($to ?: 'Today');
    }

    /** 403 unless the user is a superuser or has any of the given permissions. */
    protected function authorizeAccess(string ...$permissions): void
    {
        $user = Auth::user();
        abort_unless(collect($permissions)->contains(fn ($p) => $user->hasAccess($p)), 403);
    }
}
