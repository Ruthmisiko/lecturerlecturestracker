<?php

namespace App\Http\Controllers;

use PDF;
use App\Exports\CollectionExport;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\PaymentReference;
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
                ->when($deptIds, fn ($q) => $q->inDepartments($deptIds))->count(),
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

        // A lecturer who taught in several departments appears once in each, with that department's figures.
        $rows = $this->reportRows($from, $to, $statuses, $overrides)
            ->flatMap(fn ($r) => LecturerPayroll::slips($r))
            // Only lecturers who taught in the period (or whose row was adjusted).
            ->filter(fn ($r) => $r->total_sessions > 0 || array_key_exists($r->id, $overrides))
            ->when($departmentId, fn ($c) => $c->where('department_id', $departmentId))
            ->values();

        // Voucher numbers / transaction refs: saved ones, overridden by values typed on the page (carried into the downloads).
        $saved = PaymentReference::where('period_key', PaymentReference::keyFor($from, $to))->get()->keyBy('lecturer_id');
        $typed = fn (string $key, int $max) => collect((array) $request->input($key, []))
            ->filter(fn ($v, $id) => ctype_digit((string) $id) && is_string($v))
            ->map(fn ($v) => mb_substr(trim($v), 0, $max))
            ->all();
        $pvTyped  = $typed('pv', 50);
        $refTyped = $typed('ref', 100);
        $pv = $refs = [];
        foreach ($rows as $r) {
            $r->pv_no           = $pvTyped[$r->id]  ?? $saved[$r->id]->pv_no ?? '';
            $r->transaction_ref = $refTyped[$r->id] ?? $saved[$r->id]->transaction_ref ?? '';
            $pv[$r->id]         = $r->pv_no;
            $refs[$r->id]       = $r->transaction_ref;
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

        return view('dashboard.report', compact('sections', 'totals', 'from', 'to', 'statuses', 'departments', 'department', 'overrides', 'pv', 'refs'));
    }

    /** Saves voucher numbers and transaction refs (one row or many) for a report period. */
    public function saveReferences(Request $request)
    {
        $this->authorizeAccess('payment-report.view');

        $data = $request->validate([
            'from'                    => 'nullable|date_format:Y-m-d',
            'to'                      => 'nullable|date_format:Y-m-d',
            'items'                   => 'required|array|min:1|max:500',
            'items.*.lecturer_id'     => 'required|integer',
            'items.*.pv_no'           => 'nullable|string|max:50',
            'items.*.transaction_ref' => 'nullable|string|max:100',
        ]);

        $key = PaymentReference::keyFor($data['from'] ?? null, $data['to'] ?? null);
        $ids = Lecturer::whereIn('id', collect($data['items'])->pluck('lecturer_id'))->pluck('id')->all(); // institution-scoped

        $saved = 0;
        foreach ($data['items'] as $item) {
            if (!in_array((int) $item['lecturer_id'], $ids, true)) {
                continue;
            }

            $pv    = trim((string) ($item['pv_no'] ?? ''));
            $ref   = trim((string) ($item['transaction_ref'] ?? ''));
            $match = ['lecturer_id' => $item['lecturer_id'], 'period_key' => $key];

            if ($pv === '' && $ref === '') {
                PaymentReference::where($match)->delete();
            } else {
                PaymentReference::updateOrCreate($match, ['pv_no' => $pv ?: null, 'transaction_ref' => $ref ?: null, 'user_id' => Auth::id()]);
            }
            $saved++;
        }

        return response()->json(['saved' => $saved]);
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
        $statuses = $this->statuses($request);
        $deptKey  = $request->filled('department') ? $request->integer('department') : null;

        [$lecturer, $row] = $this->payslipRow($lecturerId, $from, $to, $statuses);
        $slips = LecturerPayroll::slips($row, $deptKey);
        abort_unless($slips, 404);

        // One page per department when no single department was asked for.
        return $this->payslipPdfFor($lecturer, $slips, $from, $to, $statuses)
            ->download($this->payslipFileName($lecturer, count($slips) === 1 && count($row->departments) > 1 ? $slips[0] : null));
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
        $statuses = $this->statuses($request);
        [$lecturer, $row] = $this->payslipRow($lecturerId, $from, $to, $statuses);

        // A lecturer who taught in several departments gets one email per department, each with that payslip.
        // With a department chosen on the dashboard, only that department's payslip is emailed.
        $deptKey     = $request->filled('department') ? $request->integer('department') : null;
        $slips       = LecturerPayroll::slips($row, $deptKey);
        abort_unless($slips, 404);
        $split       = count($row->departments ?? []) > 1;
        $period      = mb_substr(trim((string) $request->input('period_text')), 0, 100) ?: $this->periodLabel($from, $to);
        $institution = \App\Models\Institution::find(Auth::user()->currentInstitutionId())?->name ?: 'Accounts Office';
        $sent        = [];

        foreach ($slips as $slip) {
            try {
                $config->mailer()->to($data['to'])->send(new \App\Mail\PayslipMail(
                    \App\Models\EmailTemplate::fillPlaceholders($data['subject'], $slip, $period, $institution),
                    \App\Models\EmailTemplate::fillPlaceholders($data['body'], $slip, $period, $institution),
                    $this->payslipPdfFor($lecturer, [$slip], $from, $to, $statuses)->output(),
                    $this->payslipFileName($lecturer, $split ? $slip : null),
                    $config->from_address, $config->from_name,
                ));
            } catch (\Throwable $e) {
                report($e);
                $done = $sent ? ' (already sent for ' . implode(', ', $sent) . ')' : '';
                return response()->json(['message' => "Sending failed for {$slip->department}{$done}: " . $e->getMessage()], 422);
            }
            $sent[] = $slip->department;
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

        $message = $split && count($slips) === 1
            ? "{$sent[0]} payslip sent to {$data['to']}."
            : ($split
            ? count($slips) . " payslips (one per department: " . implode(', ', $sent) . ") sent to {$data['to']}."
            : "Payslip sent to {$data['to']}.");

        return response()->json(['message' => $message, 'sent_at' => now()->format('d M Y, H:i')]);
    }

    /** [Lecturer, payroll row] for a lecturer's payslip. */
    protected function payslipRow($lecturerId, ?string $from, ?string $to, ?array $statuses): array
    {
        $lecturer = Lecturer::with('department', 'institution')->findOrFail($lecturerId);
        $row      = (new LecturerPayroll(Auth::user(), $from, $to, $statuses))->rows()->firstWhere('id', $lecturer->id);

        abort_unless($row, 404);

        return [$lecturer, $row];
    }

    /** Payslip PDF, one page per slip (see LecturerPayroll::slips), from already-calculated figures. */
    protected function payslipPdfFor(Lecturer $lecturer, array $slips, ?string $from, ?string $to, ?array $statuses)
    {
        $period = $this->periodLabel($from, $to);

        return PDF::loadView('dashboard.payslip_pdf', compact('lecturer', 'slips', 'period', 'statuses', 'from', 'to'))
            ->setPaper([0, 0, 297.64, 595.28]); // half-width A4: 105 × 210 mm
    }

    /** e.g. payslip_akello_shem_juma.pdf, or payslip_akello_shem_juma_clinical_medicine.pdf for one department's slip. */
    protected function payslipFileName(Lecturer $lecturer, ?object $slip = null): string
    {
        return 'payslip_' . str($lecturer->name)->slug('_') . ($slip ? '_' . str($slip->department)->slug('_') : '') . '.pdf';
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

    /** Clash statuses whose hours are payable; null = all statuses. Defaults to OK only when none is chosen. */
    protected function statuses(Request $request): ?array
    {
        if (!$request->has('statuses')) {
            return ['ok'];
        }

        return LecturerPayroll::normaliseStatuses((array) $request->input('statuses'));
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
