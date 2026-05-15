<?php

namespace App\Http\Controllers;

use PDF;
use Flash;
use Illuminate\Http\Request;
use App\Exports\LectureExport;
use App\Imports\LectureImport;
use App\Models\Classs;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\LectureAdministered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LectureTemplateExport;
use App\Http\Controllers\AppBaseController;
use App\Repositories\LectureAdministeredRepository;
use App\Http\Requests\CreateLectureAdministeredRequest;
use App\Http\Requests\UpdateLectureAdministeredRequest;

class LectureAdministeredController extends AppBaseController
{
    /** @var LectureAdministeredRepository $lectureAdministeredRepository*/
    private $lectureAdministeredRepository;

    public function __construct(LectureAdministeredRepository $lectureAdministeredRepo)
    {
        $this->lectureAdministeredRepository = $lectureAdministeredRepo;
    }

    /**
     * Display a listing of the LectureAdministered.
     */
   public function index(Request $request)
{
    $user    = Auth::user();
    $ownerId = $user->user_id ?? $user->id;

    // Fetch ALL owner records once — used for clash detection and filtering
    $allRecords = LectureAdministered::with(['lecturer', 'classs', 'unit', 'department'])
        ->where('user_id', $ownerId)
        ->when($user->scopedDepartmentId(), fn($q) => $q->where('department_id', $user->scopedDepartmentId()))
        ->get();

    // Build clash pools from unfiltered records
    [$ownClashPool, $clashes] = $this->buildClashPools($allRecords);

    $duplicates = LectureAdministered::where('user_id', $ownerId)
        ->select('lecturer_id', 'classs_id', 'lecture_date', 'start_time', 'end_time')
        ->groupBy('lecturer_id', 'classs_id', 'lecture_date', 'start_time', 'end_time')
        ->havingRaw('COUNT(*) > 1')
        ->with(['lecturer', 'classs'])
        ->get();

    // Apply filters in PHP so status filter works consistently
    $filtered = $allRecords->filter(function ($item) use ($request, $ownClashPool, $clashes) {
        if ($request->filled('lecturer') &&
            !str_contains(strtolower($item->lecturer->name ?? ''), strtolower($request->lecturer))) {
            return false;
        }
        if ($request->filled('class') &&
            !str_contains(strtolower($item->classs->name ?? ''), strtolower($request->class))) {
            return false;
        }
        if ($request->filled('lecture_date') && $item->lecture_date !== $request->lecture_date) {
            return false;
        }
        if ($request->filled('department_id') && $item->department_id != $request->department_id) {
            return false;
        }
        if ($request->filled('status')) {
            $ownClash  = $this->isOwnClash($item, $ownClashPool);
            $clashWith = $this->isClashWith($item, $clashes);
            return match ($request->status) {
                'own_clash'  => $ownClash,
                'clash_with' => $clashWith,
                'ok'         => !$ownClash && !$clashWith,
                default      => true,
            };
        }
        return true;
    });

    $perPage = in_array((int) $request->get('per_page', 10), [10, 50, 100, 150, 200])
        ? (int) $request->get('per_page', 10)
        : 10;

    $page = $request->get('page', 1);
    $lectureAdministereds = new \Illuminate\Pagination\LengthAwarePaginator(
        $filtered->values()->forPage($page, $perPage),
        $filtered->count(),
        $perPage,
        $page,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    $departments = Department::where('user_id', $ownerId)->orderBy('name')->get();

    return view('lecture_administereds.index',
        compact('lectureAdministereds', 'duplicates', 'clashes', 'ownClashPool', 'departments'));
}

private function buildClashPools($allRecords): array
{
    // Own clash pool — same lecturer, same date, overlapping time (any class/unit)
    $ownClashPool = $allRecords
        ->groupBy(fn($i) => $i->lecturer_id . '_' . $i->lecture_date)
        ->filter(function ($group) {
            $items = $group->values();
            for ($i = 0; $i < $items->count(); $i++) {
                for ($j = $i + 1; $j < $items->count(); $j++) {
                    if (!($items[$i]->end_time <= $items[$j]->start_time ||
                          $items[$i]->start_time >= $items[$j]->end_time)) {
                        return true;
                    }
                }
            }
            return false;
        })
        ->flatMap(fn($g) => $g);

    // Clash with other lecturer — same class, same date, overlapping time, different lecturer
    $clashes = $allRecords
        ->groupBy(fn($i) => $i->classs_id . '_' . $i->lecture_date . '_' . $i->start_time . '_' . $i->end_time)
        ->filter(fn($g) => $g->count() > 1)
        ->flatMap(fn($g) => $g);

    return [$ownClashPool, $clashes];
}

private function isOwnClash($item, $ownClashPool): bool
{
    return $ownClashPool->contains(fn($c) =>
        $c->id != $item->id &&
        $c->lecturer_id == $item->lecturer_id &&
        $c->lecture_date == $item->lecture_date &&
        !($item->end_time <= $c->start_time || $item->start_time >= $c->end_time)
    );
}

private function isClashWith($item, $clashes): bool
{
    return $clashes->contains(fn($c) =>
        $c->id != $item->id &&
        $c->classs_id == $item->classs_id &&
        $c->lecture_date == $item->lecture_date &&
        $c->lecturer_id != $item->lecturer_id &&
        !($item->end_time <= $c->start_time || $item->start_time >= $c->end_time)
    );
}

public function exportExcel(Request $request)
{
    [$records, $ownClashPool, $clashes] = $this->fetchExportData($request);
    return Excel::download(new LectureExport($records, $ownClashPool, $clashes), 'lecture_report.xlsx');
}

public function exportPdf(Request $request)
{
    [$records, $ownClashPool, $clashes] = $this->fetchExportData($request);

    $department = null;
    if ($request->filled('department_id')) {
        $department = Department::find($request->department_id);
    }

    $pdf = PDF::loadView('lecture_administereds.pdf', [
        'records'      => $records,
        'ownClashPool' => $ownClashPool,
        'clashes'      => $clashes,
        'status'       => $request->status,
        'department'   => $department,
        'filters'      => $request->only('lecturer', 'class', 'lecture_date', 'department_id', 'status'),
    ])->setPaper('a4', 'landscape');

    return $pdf->download('lecture_report.pdf');
}

private function fetchExportData(Request $request): array
{
    $user    = Auth::user();
    $ownerId = $user->user_id ?? $user->id;

    $allRecords = LectureAdministered::with(['lecturer', 'classs', 'unit', 'department'])
        ->where('user_id', $ownerId)
        ->when($user->scopedDepartmentId(), fn($q) => $q->where('department_id', $user->scopedDepartmentId()))
        ->get();

    [$ownClashPool, $clashes] = $this->buildClashPools($allRecords);

    $records = $allRecords->filter(function ($item) use ($request, $ownClashPool, $clashes) {
        if ($request->filled('lecturer') &&
            !str_contains(strtolower($item->lecturer->name ?? ''), strtolower($request->lecturer))) return false;
        if ($request->filled('class') &&
            !str_contains(strtolower($item->classs->name ?? ''), strtolower($request->class))) return false;
        if ($request->filled('lecture_date') && $item->lecture_date !== $request->lecture_date) return false;
        if ($request->filled('department_id') && $item->department_id != $request->department_id) return false;
        if ($request->filled('status')) {
            $ownClash  = $this->isOwnClash($item, $ownClashPool);
            $clashWith = $this->isClashWith($item, $clashes);
            return match ($request->status) {
                'own_clash'  => $ownClash,
                'clash_with' => $clashWith,
                'ok'         => !$ownClash && !$clashWith,
                default      => true,
            };
        }
        return true;
    })->values();

    return [$records, $ownClashPool, $clashes];
}

    /**
     * Show the form for creating a new LectureAdministered.
     */
    public function create()
    {
        $user = Auth::user();
        $ownerId = $user->user_id ?? $user->id;

        $deptScope   = $user->scopedDepartmentId();

        $dq          = Department::where('user_id', $ownerId);
        if ($deptScope) $dq->where('id', $deptScope);
        $departments = $dq->get();

        $lq        = Lecturer::where('user_id', $ownerId);
        if ($deptScope) $lq->where('department_id', $deptScope);
        $lecturers = $lq->get(['id', 'name', 'department_id']);

        $classes   = Classs::where('user_id', $ownerId)->pluck('name', 'id');

        $uq    = \App\Models\Unit::where('user_id', $ownerId);
        if ($deptScope) $uq->where('department_id', $deptScope);
        $units = $uq->get(['id', 'name', 'unitCode', 'department_id']);

        return view('lecture_administereds.create', compact('departments', 'lecturers', 'classes', 'units'));
    }

    /**
     * Store a newly created LectureAdministered in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'department_id'         => 'nullable|exists:departments,id',
            'records'               => 'required|array|min:1',
            'records.*.lecturer_id' => 'required|exists:lecturers,id',
            'records.*.classs_id'   => 'required|exists:classses,id',
            'records.*.start_time'  => 'required|date_format:H:i',
            'records.*.end_time'    => 'required|date_format:H:i|after:records.*.start_time',
            'records.*.dates'       => 'required|array|min:1',
            'records.*.dates.*'     => 'date',
        ]);

        $user    = Auth::user();
        $ownerId = $user->user_id ?? $user->id;
        $deptId  = $request->department_id;

        DB::transaction(function () use ($request, $ownerId, $deptId) {
            foreach ($request->records as $record) {
                foreach ($record['dates'] as $date) {
                    LectureAdministered::create([
                        'user_id'       => $ownerId,
                        'department_id' => $deptId,
                        'lecturer_id'   => $record['lecturer_id'],
                        'classs_id'     => $record['classs_id'],
                        'unit_id'       => $record['unit_id'] ?? null,
                        'lecture_date'  => $date,
                        'start_time'    => $record['start_time'],
                        'end_time'      => $record['end_time'],
                    ]);
                }
            }
        });

        Flash::success('Lecture(s) recorded successfully.');
        return redirect(route('lecture-administereds.index'));
    }


    /**
     * Display the specified LectureAdministered.
     */
    public function show($id)
    {
        $lectureAdministered = $this->lectureAdministeredRepository->find($id);

        if (empty($lectureAdministered)) {
            Flash::error('Lecture Administered not found');

            return redirect(route('lectureAdministereds.index'));
        }

        return view('lecture_administereds.show')->with('lectureAdministered', $lectureAdministered);
    }

    /**
     * Show the form for editing the specified LectureAdministered.
     */
   public function edit($id)
{
    $lectureAdministered = $this->lectureAdministeredRepository->find($id);

      $lecturers = \App\Models\Lecturer::where('user_id', Auth::id())->pluck('name', 'id');
    $classes = \App\Models\Classs::where('user_id', Auth::id())->pluck('name', 'id');
    return view('lecture_administereds.edit', compact('lectureAdministered', 'lecturers', 'classes'));
}

    /**
     * Update the specified LectureAdministered in storage.
     */
  public function update($id, UpdateLectureAdministeredRequest $request)
    {
        $request->validate([
            'lecturer_id' => 'required|exists:lecturers,id',
            'classs_id' => 'required|exists:classses,id',
            'lecture_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time'
        ]);

        $lectureAdministered = $this->lectureAdministeredRepository->find($id);



        if (empty($lectureAdministered)) {
            Flash::error('Lecture Administered not found');
            return redirect(route('lecture-administereds.index'));
        }

        $lectureAdministered->update([
            'lecturer_id' => $request->lecturer_id,
            'classs_id' => $request->classs_id,
            'lecture_date' => $request->lecture_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        Flash::success('Lecture Administered updated successfully.');
        return redirect(route('lecture-administereds.index'));
    }

    /**
     * Remove the specified LectureAdministered from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $lectureAdministered = $this->lectureAdministeredRepository->find($id);

        if (empty($lectureAdministered)) {
            Flash::error('Lecture Administered not found');

            return redirect(route('lectureAdministereds.index'));
        }

        $this->lectureAdministeredRepository->delete($id);

        Flash::success('Lecture Administered deleted successfully.');

        return redirect(route('lectureAdministereds.index'));
    }

    public function downloadTemplate()
    {
        $userId = Auth::id();
        return Excel::download(new LectureTemplateExport($userId), 'lecture_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
        ]);
        try {
            Excel::import(new LectureImport, $request->file('excel_file'));
            Flash::success('Lecture schedule imported successfully.');
        } catch (\Exception $e) {
            Flash::error('Error importing file: ' . $e->getMessage());
        }
        return redirect(route('lecture-administereds.index'));
    }

}
