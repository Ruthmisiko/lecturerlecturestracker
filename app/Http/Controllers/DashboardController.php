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
        $statuses    = $this->statuses($request);
        $rows   = (new LecturerPayroll(Auth::user(), $from, $to, $statuses))->rows();
        $totals = LecturerPayroll::totals($rows);

        if ($request->get('format') === 'excel') {
            return $this->excel($rows, $totals, $from, $to, $statuses);
        }

        if ($request->get('format') === 'pdf') {
            $period = $this->periodLabel($from, $to);
            return PDF::loadView('dashboard.report_pdf', compact('rows', 'totals', 'period', 'statuses'))
                ->setPaper('a4', 'landscape')
                ->download('lecturer_payment_report.pdf');
        }

        return view('dashboard.report', compact('rows', 'totals', 'from', 'to', 'statuses'));
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

    protected function excel($rows, $totals, $from, $to, ?array $statuses)
    {
        $data = collect([
            ['Lecturer Payment Report — ' . $this->periodLabel($from, $to) . ' — Payable: ' . self::statusLabel($statuses)],
            [],
            ['Lecturer', 'Department', 'KRA PIN', 'Sessions', 'Total Hours', 'Rate / Hr', 'Gross Amount', 'Tax', 'Housing Levy', 'Net Pay'],
        ]);

        foreach ($rows as $r) {
            $data->push([$r->name, $r->department, $r->kra_pin, $r->sessions, $r->hours, $r->rate, $r->gross, $r->tax, $r->housing, $r->net]);
        }

        $data->push(['TOTAL', '', '', $totals->sessions, $totals->hours, '', $totals->gross, $totals->tax, $totals->housing, $totals->net]);

        return Excel::download(new CollectionExport($data), 'lecturer_payment_report.xlsx');
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
