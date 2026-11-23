<?php

namespace App\Http\Controllers;

use Flash;
use App\Models\Department;
use App\Models\LectureAdministered;
use App\Services\StaffVisitNarrator;
use App\Services\TravelBudgets;
use App\Models\Lecturer;
use App\Models\StaffVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StaffVisitController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()?->hasAccess('staff-visit.manage'), 403);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user    = Auth::user();
        $deptIds = $user->scopedDepartmentIds();

        $visits = StaffVisit::with('lecturer')
            ->where('user_id', $user->ownerId())
            ->when($deptIds, fn ($q) => $q->whereHas('lecturer', fn ($l) => $l->inDepartments($deptIds)))
            ->when($request->filled('lecturer_id'), fn ($q) => $q->where('lecturer_id', $request->lecturer_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('end_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('start_date', '<=', $request->to))
            ->orderByDesc('start_date')->orderByDesc('id')
            ->get();

        $clashes = $this->clashesFor($visits);

        if ($request->input('clash') === 'yes') {
            $visits = $visits->filter(fn ($v) => $clashes[$v->id]->isNotEmpty())->values();
        } elseif ($request->input('clash') === 'no') {
            $visits = $visits->filter(fn ($v) => $clashes[$v->id]->isEmpty())->values();
        }

        $totals = [
            'count'    => $visits->count(),
            'days'     => $visits->sum('days'),
            'amount'   => $visits->sum('total_amount'),
            'pending'  => $visits->where('status', 'pending')->sum('total_amount'),
            'clashing' => $visits->filter(fn ($v) => $clashes[$v->id]->isNotEmpty())->count(),
        ];

        $perPage = 25;
        $page    = (int) $request->get('page', 1);
        $paged   = new LengthAwarePaginator(
            $visits->forPage($page, $perPage)->values(), $visits->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('staff_visits.index', [
            'visits'    => $paged,
            'clashes'   => $clashes,
            'totals'    => $totals,
            'lecturers' => $this->lecturers(),
        ]);
    }

    /** Claims sheet laid out like the college's "Staff Pending Claims" report. */
    public function report(Request $request)
    {
        return view('staff_visits.report', $this->reportData($request) + [
            'lecturers'   => $this->lecturers(),
            'departments' => $this->departments(),
        ]);
    }

    public function reportPdf(Request $request)
    {
        $pdf = \PDF::loadView('staff_visits.report_pdf', $this->reportData($request))->setPaper('a3', 'landscape');

        return $pdf->download('staff_claims.pdf');
    }

    public function reportExcel(Request $request)
    {
        $data = $this->reportData($request);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StaffClaimsExport($data['rows'], $data['institution'], $data['heading'], $data['subheading']),
            'staff_claims.xlsx'
        );
    }

    /** Rows and headings for the claims report; defaults to everything not yet paid, as at today. */
    private function reportData(Request $request): array
    {
        $user    = Auth::user();
        $deptIds = $user->scopedDepartmentIds();
        $status  = $request->input('status', 'pending');
        $asAt    = $request->filled('as_at') ? Carbon::parse($request->as_at) : now();

        $visits = StaffVisit::with(['lecturer', 'department'])
            ->where('user_id', $user->ownerId())
            ->when($deptIds, fn ($q) => $q->whereHas('lecturer', fn ($l) => $l->inDepartments($deptIds)))
            ->when($status === 'pending' || $status === 'paid', fn ($q) => $q->where('status', $status))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('lecturer_id'), fn ($q) => $q->where('lecturer_id', $request->lecturer_id))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('start_date', '>=', $request->from))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('start_date', '<=', $request->to))
            ->whereDate('date_of_entry', '<=', $asAt->toDateString())
            ->orderBy('date_of_entry')->orderBy('id')
            ->get();

        $rows = $visits->values()->map(fn ($v, $i) => (object) [
            'no'            => $i + 1,
            'entry_date'    => $v->date_of_entry?->format('d.m.y'),
            'name'          => strtoupper($v->lecturer->name ?? ''),
            'personal_no'   => $v->lecturer->personal_number ?? '',
            'job_group'     => $v->lecturer->job_group ?? '',
            'voucher_no'    => $v->voucher_number,
            'dates'         => $v->start_date->equalTo($v->end_date)
                ? $v->start_date->format('d.m.y')
                : $v->start_date->format('d.m.y') . ' TO ' . $v->end_date->format('d.m.y'),
            'purpose'       => strtoupper($v->purpose),
            'narration'     => $v->narration,
            'itinerary'     => strtoupper((string) ($v->itinerary ?: $v->location)),
            'sponsor'       => strtoupper((string) $v->sponsor),
            'days'          => $v->days,
            'rate'          => $v->per_diem_rate,
            'fare'          => $v->fare,
            'meals'         => $v->meals,
            'other'         => $v->remuneration,
            'total'         => $v->total_amount,
            'status'        => $v->status,
            'department'    => $v->department->name ?? '',
        ]);

        $institution = ($id = $user->currentInstitutionId()) ? \App\Models\Institution::find($id)?->name : null;
        $label       = ['pending' => 'PENDING CLAIMS', 'paid' => 'PAID CLAIMS'][$status] ?? 'CLAIMS';
        $dept        = $request->filled('department_id') ? Department::find($request->department_id)?->name : null;

        return [
            'rows'        => $rows,
            'institution' => strtoupper($institution ?: config('app.name')),
            'heading'     => 'STAFF ' . $label . ' AS AT ' . strtoupper($asAt->format('jS F Y')),
            'subheading'  => $dept ? strtoupper($dept) : '',
            'showOther'   => $rows->sum('other') > 0,
            'total'       => $rows->sum('total'),
        ];
    }

    public function create()
    {
        return view('staff_visits.form', [
            'visit'       => new StaffVisit(['type' => 'visit', 'status' => 'pending', 'date_of_entry' => now()->toDateString(), 'sponsor' => 'Campus']),
            'lecturers'   => $this->lecturers(),
            'departments' => $this->departments(),
        ]);
    }

    public function store(Request $request)
    {
        $visit = StaffVisit::create($this->validated($request) + ['user_id' => Auth::user()->ownerId()]);

        $this->flashSaved($visit, 'Staff visit recorded.');

        return redirect()->route('staff-visits.index');
    }

    public function edit($id)
    {
        return view('staff_visits.form', ['visit' => $this->find($id), 'lecturers' => $this->lecturers(), 'departments' => $this->departments()]);
    }

    public function update(Request $request, $id)
    {
        $visit = $this->find($id);
        $visit->update($this->validated($request));

        $this->flashSaved($visit, 'Staff visit updated.');

        return redirect()->route('staff-visits.index');
    }

    public function markPaid(Request $request, $id)
    {
        $visit = $this->find($id);
        $visit->update(['status' => 'paid', 'paid_on' => $request->input('paid_on') ?: now()->toDateString()]);

        Flash::success('Marked as paid.');

        return redirect()->back(fallback: route('staff-visits.index'));
    }

    public function destroy($id)
    {
        $this->find($id)->delete();

        Flash::success('Staff visit deleted.');

        return redirect()->route('staff-visits.index');
    }

    private function find($id): StaffVisit
    {
        return StaffVisit::where('user_id', Auth::user()->ownerId())->findOrFail($id);
    }

    private function lecturers()
    {
        $user = Auth::user();

        return Lecturer::where('user_id', $user->ownerId())
            ->when($user->scopedDepartmentIds(), fn ($q, $ids) => $q->inDepartments($ids))
            ->orderBy('name')->get(['id', 'name', 'id_number', 'personal_number', 'job_group', 'per_diem_rate', 'department_id']);
    }

    private function departments()
    {
        $user = Auth::user();

        return Department::where('user_id', $user->ownerId())
            ->when($user->scopedDepartmentIds(), fn ($q, $ids) => $q->whereIn('id', $ids))
            ->orderBy('name')->get(['id', 'name']);
    }

    /** AJAX: draft the voucher narration from what is on the form. */
    public function narrate(Request $request)
    {
        $data = $request->validate([
            'lecturer_id' => 'required|exists:lecturers,id',
            'type'        => 'nullable|string',
            'purpose'     => 'required|string|max:255',
            'location'    => 'nullable|string|max:255',
            'itinerary'   => 'nullable|string|max:255',
            'sponsor'     => 'nullable|string|max:255',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date',
        ], ['purpose.required' => 'Enter the purpose of the activity first.', 'start_date.required' => 'Enter the dates first.']);

        return response()->json(app(StaffVisitNarrator::class)->narrate($this->narrationInput($data)));
    }

    private function narrationInput(array $data): array
    {
        return [
            'lecturer'   => Lecturer::find($data['lecturer_id'])?->name,
            'type'       => StaffVisit::TYPES[$data['type'] ?? ''] ?? ($data['type'] ?? ''),
            'purpose'    => $data['purpose'] ?? '',
            'location'   => $data['location'] ?? '',
            'itinerary'  => $data['itinerary'] ?? '',
            'sponsor'    => $data['sponsor'] ?? '',
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'] ?? $data['start_date'],
        ];
    }

    /** Warns right after saving when the visit overlaps lectures already administered. */
    private function flashSaved(StaffVisit $visit, string $message): void
    {
        $clashes = $visit->clashingLectures();
        $notes   = [];

        if ($clashes->isNotEmpty()) {
            $notes[] = 'Clash: ' . $visit->lecturer->name . ' has ' . $clashes->count()
                . ' lecture(s) administered while on this visit. Review them on the Staff Visits list.';
        }

        $remaining = app(TravelBudgets::class)->remainingFor(Auth::user()->ownerId(), $visit->department_id, $visit->start_date);
        if ($remaining !== null && $remaining < 0) {
            $notes[] = 'Over budget: this department has exceeded its travel budget by KES ' . number_format(abs($remaining), 2) . '.';
        }

        $notes ? Flash::warning($message . ' ' . implode(' ', $notes)) : Flash::success($message);
    }

    /**
     * Lectures clashing with each visit, keyed by visit id. One query for all visits.
     *
     * @return array<int, \Illuminate\Support\Collection>
     */
    private function clashesFor($visits): array
    {
        if ($visits->isEmpty()) {
            return [];
        }

        $lectures = LectureAdministered::with(['classs', 'unit'])
            ->whereIn('lecturer_id', $visits->pluck('lecturer_id')->unique())
            ->whereBetween('lecture_date', [
                $visits->min(fn ($v) => $v->start_date->format('Y-m-d')),
                $visits->max(fn ($v) => $v->end_date->format('Y-m-d')),
            ])
            ->orderBy('lecture_date')->orderBy('start_time')
            ->get()
            ->groupBy('lecturer_id');

        $result = [];
        foreach ($visits as $visit) {
            $result[$visit->id] = $lectures->get($visit->lecturer_id, collect())
                ->filter(function ($l) use ($visit) {
                    $date = substr((string) $l->lecture_date, 0, 10);
                    return $date >= $visit->start_date->format('Y-m-d')
                        && $date <= $visit->end_date->format('Y-m-d')
                        && $visit->overlapsLecture($l);
                })->values();
        }

        return $result;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'lecturer_id'   => 'required|exists:lecturers,id',
            'department_id' => 'nullable|exists:departments,id',
            'date_of_entry' => 'nullable|date',
            'sponsor'       => 'nullable|string|max:255',
            'itinerary'     => 'nullable|string|max:255',
            'narration'     => 'nullable|string|max:1000',
            'days'          => 'nullable|integer|min:1|max:366',
            'fare'          => 'nullable|numeric|min:0',
            'meals'         => 'nullable|numeric|min:0',
            'type'          => 'required|in:' . implode(',', array_keys(StaffVisit::TYPES)),
            'purpose'       => 'required|string|max:255',
            'location'      => 'nullable|string|max:255',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'start_time'    => 'nullable|date_format:H:i|required_with:end_time',
            'end_time'      => 'nullable|date_format:H:i|required_with:start_time',
            'per_diem_rate' => 'required|numeric|min:0',
            'remuneration'  => 'nullable|numeric|min:0',
            'status'        => 'required|in:pending,paid',
            'voucher_number' => 'nullable|string|max:100',
            'paid_on'       => 'nullable|date',
            'remarks'       => 'nullable|string|max:2000',
        ]);

        abort_unless(Lecturer::where('user_id', Auth::user()->ownerId())->whereKey($data['lecturer_id'])->exists(), 403);

        // A single-day visit's end time must be after its start time.
        if ($data['start_date'] === $data['end_date'] && !empty($data['start_time']) && $data['end_time'] <= $data['start_time']) {
            throw ValidationException::withMessages(['end_time' => 'End time must be after the start time.']);
        }

        $data['remuneration']   = $data['remuneration'] ?? 0;
        $data['fare']           = $data['fare'] ?? 0;
        $data['meals']          = $data['meals'] ?? 0;
        $data['date_of_entry']  = $data['date_of_entry'] ?? now()->toDateString();

        // Days are worked out from the dates; a typed number that differs is kept as a manual entry.
        $auto = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
        $data['days_manual'] = !empty($data['days']) && (int) $data['days'] !== $auto;
        $data['days']        = $data['days_manual'] ? (int) $data['days'] : $auto;

        // Charged to the lecturer's main department unless another is chosen.
        if (empty($data['department_id'])) {
            $data['department_id'] = Lecturer::find($data['lecturer_id'])?->department_id;
        }

        // Voucher narration: written by AI from the nature of the activity when left empty.
        if (empty($data['narration'])) {
            $data['narration'] = app(StaffVisitNarrator::class)->narrate($this->narrationInput($data))['text'];
        }
        $data['paid_on']      = $data['status'] === 'paid' ? ($data['paid_on'] ?? now()->toDateString()) : null;

        return $data;
    }
}
