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
        if (!$user->isSuperAdmin()) {
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

        return view('dashboard', compact('rows', 'totals', 'stats', 'missingRate', 'departmentSummary', 'from', 'to', 'statuses'));
    }

    public function report(Request $request)
    {
        $this->authorizeSuperuser();

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
        $this->authorizeSuperuser();

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
        $this->authorizeSuperuser();

        [$from, $to] = $this->period($request);
        $statuses    = $this->statuses($request);
        $lecturer    = Lecturer::with('department', 'institution')->findOrFail($lecturerId);
        $row         = (new LecturerPayroll(Auth::user(), $from, $to, $statuses))->rows()->firstWhere('id', $lecturer->id);

        abort_unless($row, 404);

        $period = $this->periodLabel($from, $to);
        $slipNo = 'PS-' . str_pad($lecturer->id, 4, '0', STR_PAD_LEFT) . '-' . ($to ? str_replace('-', '', substr($to, 0, 7)) : now()->format('Ym'));

        return PDF::loadView('dashboard.payslip_pdf', compact('lecturer', 'row', 'period', 'slipNo', 'statuses'))
            ->setPaper([0, 0, 297.64, 595.28]) // half-width A4: 105 × 210 mm
            ->download('payslip_' . str($lecturer->name)->slug('_') . '.pdf');
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

    protected function authorizeSuperuser(): void
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
    }
}
