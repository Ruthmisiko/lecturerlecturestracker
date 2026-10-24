<?php

namespace App\Http\Controllers;

use PDF;
use Flash;
use App\Exports\CollectionExport;
use App\Models\Lecturer;
use App\Models\PaymentSchedule;
use App\Services\LecturerPayroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PaymentScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()?->isSuperAdmin(), 403);
            return $next($request);
        });
    }

    public function index()
    {
        $schedules = PaymentSchedule::with('items')->latest('schedule_date')->latest('id')->paginate(15);

        return view('payment_schedules.index', compact('schedules'));
    }

    public function create(Request $request)
    {
        [$from, $to, $statuses] = $this->filters($request);
        $amounts   = $this->netPay($from, $to, $statuses);
        $lecturers = $this->lecturers();

        // Start with every lecturer, pre-filled from their saved details.
        $items = $lecturers->map(fn ($l) => [
            'lecturer_id'    => $l->id,
            'name'           => $l->name,
            'account_number' => $l->account_number,
            'amount'         => $amounts[$l->id] ?? 0,
            'statuses'       => null, // all statuses until changed on the row
            'bank'           => $l->bank_name,
            'branch'         => $l->bank_branch,
            'id_number'      => $l->id_number,
        ])->values();

        $schedule = new PaymentSchedule([
            'title'                  => trim(strtoupper(\App\Models\Institution::find(Auth::user()->currentInstitutionId())?->name ?? '') . ' PAYMENT SCHEDULE'),
            'schedule_date'          => now()->toDateString(),
            'period_from'            => $from,
            'period_to'              => $to,
            'statuses'               => $statuses,
            'authorized_by_position' => 'ACCOUNTANT I/C',
            'approved_by_position'   => 'PRINCIPAL',
        ]);

        return view('payment_schedules.form', compact('schedule', 'items', 'lecturers'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $schedule = DB::transaction(function () use ($data) {
            $schedule = PaymentSchedule::create($data['schedule'] + ['user_id' => Auth::id()]);
            $this->saveItems($schedule, $data['items']);
            return $schedule;
        });

        Flash::success('Payment schedule created.');

        return redirect()->route('payment-schedules.show', $schedule->id);
    }

    public function show($id)
    {
        $schedule = PaymentSchedule::with('items')->findOrFail($id);

        return view('payment_schedules.show', compact('schedule'));
    }

    public function edit($id)
    {
        $schedule  = PaymentSchedule::with('items')->findOrFail($id);
        $lecturers = $this->lecturers();
        $items     = $schedule->items->map(fn ($i) => $i->only(['lecturer_id', 'name', 'account_number', 'amount', 'statuses', 'bank', 'branch', 'id_number']))->values();

        return view('payment_schedules.form', compact('schedule', 'items', 'lecturers'));
    }

    public function update(Request $request, $id)
    {
        $schedule = PaymentSchedule::findOrFail($id);
        $data     = $this->validated($request);

        DB::transaction(function () use ($schedule, $data) {
            $schedule->update($data['schedule']);
            $schedule->items()->delete();
            $this->saveItems($schedule, $data['items']);
        });

        Flash::success('Payment schedule updated.');

        return redirect()->route('payment-schedules.show', $schedule->id);
    }

    public function destroy($id)
    {
        PaymentSchedule::findOrFail($id)->delete();

        Flash::success('Payment schedule deleted.');

        return redirect()->route('payment-schedules.index');
    }

    /** Net pay per lecturer for the chosen period / payable statuses (used to refresh amounts on the form). */
    public function amounts(Request $request)
    {
        [$from, $to, $statuses] = $this->filters($request);

        return response()->json($this->netPay($from, $to, $statuses));
    }

    public function pdf($id)
    {
        $schedule = PaymentSchedule::with('items')->findOrFail($id);

        return PDF::loadView('payment_schedules.pdf', compact('schedule'))
            ->setPaper('a4', 'landscape')
            ->download(str($schedule->title)->slug('_') . '_' . $schedule->schedule_date->format('Ymd') . '.pdf');
    }

    public function excel($id)
    {
        $schedule = PaymentSchedule::with('items')->findOrFail($id);

        $rows = collect([
            [$schedule->title],
            [$schedule->schedule_date->format('d.m.Y')],
            [],
            ['S/NO', 'NAME', 'ACCOUNT NO.', 'AMOUNT', 'BANK', 'BRANCH', 'ID NO.'],
        ]);

        foreach ($schedule->items as $i => $item) {
            $rows->push([$i + 1, $item->name, (string) $item->account_number, round($item->amount, 2), $item->bank, $item->branch, (string) $item->id_number]);
        }

        $rows->push(['', 'TOTAL', '', round($schedule->total(), 2), '', '', '']);
        $rows->push([]);
        $rows->push(['', 'Authorized By', '', '', '', 'APPROVED BY']);
        $rows->push(['', $schedule->authorized_by_name, '', '', '', $schedule->approved_by_name]);
        $rows->push(['', $schedule->authorized_by_position, '', '', '', $schedule->approved_by_position]);

        return Excel::download(new CollectionExport($rows), str($schedule->title)->slug('_') . '_' . $schedule->schedule_date->format('Ymd') . '.xlsx');
    }

    // ---------------------------------------------------------------------

    private function lecturers()
    {
        $user    = Auth::user();
        $deptIds = $user->scopedDepartmentIds();

        return Lecturer::where('user_id', $user->ownerId())
            ->when($deptIds, fn ($q) => $q->whereIn('department_id', $deptIds))
            ->orderBy('name')
            ->get(['id', 'name', 'account_number', 'bank_name', 'bank_branch', 'id_number']);
    }

    /** [from, to, statuses] from the request (dates as Y-m-d, statuses normalised). */
    private function filters(Request $request): array
    {
        $date = fn ($v) => $v && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;

        return [
            $date($request->input('period_from')),
            $date($request->input('period_to')),
            LecturerPayroll::normaliseStatuses($request->input('statuses')),
        ];
    }

    /** lecturer_id => net pay (4 dp). */
    private function netPay(?string $from, ?string $to, ?array $statuses): array
    {
        return (new LecturerPayroll(Auth::user(), $from, $to, $statuses))->rows()
            ->mapWithKeys(fn ($r) => [$r->id => $r->net])
            ->all();
    }

    private function validated(Request $request): array
    {
        $request->validate([
            'title'                  => 'required|string|max:255',
            'schedule_date'          => 'required|date',
            'period_from'            => 'nullable|date',
            'period_to'              => 'nullable|date|after_or_equal:period_from',
            'authorized_by_name'     => 'nullable|string|max:255',
            'authorized_by_position' => 'nullable|string|max:255',
            'approved_by_name'       => 'nullable|string|max:255',
            'approved_by_position'   => 'nullable|string|max:255',
            'items'                  => 'required|array|min:1',
            'items.*.lecturer_id'    => 'nullable|integer',
            'items.*.name'           => 'required|string|max:255',
            'items.*.account_number' => 'nullable|string|max:50',
            'items.*.amount'         => 'required|numeric|min:0',
            'items.*.statuses'       => 'nullable|array',
            'items.*.statuses.*'     => 'in:' . implode(',', array_keys(LecturerPayroll::STATUSES)),
            'items.*.bank'           => 'nullable|string|max:100',
            'items.*.branch'         => 'nullable|string|max:100',
            'items.*.id_number'      => 'nullable|string|max:50',
        ], [
            'items.required'        => 'Add at least one lecturer to the schedule.',
            'items.*.name.required' => 'Every row needs a name.',
        ]);

        return [
            'schedule' => $request->only([
                'title', 'schedule_date', 'period_from', 'period_to',
                'authorized_by_name', 'authorized_by_position', 'approved_by_name', 'approved_by_position',
            ]) + ['statuses' => LecturerPayroll::normaliseStatuses($request->input('statuses'))],
            'items' => array_values($request->input('items')),
        ];
    }

    /** Saves the rows and remembers each lecturer's bank details for next time. */
    private function saveItems(PaymentSchedule $schedule, array $items): void
    {
        $lecturers = Lecturer::whereIn('id', collect($items)->pluck('lecturer_id')->filter())->get()->keyBy('id');

        foreach ($items as $position => $item) {
            $lecturer = $lecturers->get($item['lecturer_id'] ?? null);

            $schedule->items()->create([
                'lecturer_id'    => $lecturer?->id,
                'position'       => $position,
                'name'           => trim($item['name']),
                'account_number' => trim($item['account_number'] ?? '') ?: null,
                'amount'         => $item['amount'],
                'statuses'       => LecturerPayroll::normaliseStatuses($item['statuses'] ?? null),
                'bank'           => trim($item['bank'] ?? '') ?: null,
                'branch'         => trim($item['branch'] ?? '') ?: null,
                'id_number'      => trim($item['id_number'] ?? '') ?: null,
            ]);

            if ($lecturer) {
                $lecturer->fill([
                    'account_number' => trim($item['account_number'] ?? '') ?: null,
                    'bank_name'      => trim($item['bank'] ?? '') ?: null,
                    'bank_branch'    => trim($item['branch'] ?? '') ?: null,
                ]);

                // ID numbers are unique per lecturer: only fill a missing one, never overwrite.
                $idNumber = trim($item['id_number'] ?? '');
                if (!$lecturer->id_number && ctype_digit($idNumber)
                    && !Lecturer::withoutGlobalScopes()->where('id_number', $idNumber)->exists()) {
                    $lecturer->id_number = $idNumber;
                }

                $lecturer->save();
            }
        }
    }
}
