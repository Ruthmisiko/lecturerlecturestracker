<?php

namespace App\Http\Controllers;

use Flash;
use App\Models\Unit;
use App\Models\Department;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use App\Repositories\UnitRepository;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Http\Controllers\AppBaseController;

class UnitController extends AppBaseController
{
    /** @var UnitRepository $unitRepository*/
    private $unitRepository;

    public function __construct(UnitRepository $unitRepo)
    {
        $this->unitRepository = $unitRepo;
    }

    /**
     * Display a listing of the Unit.
     */
    public function index(Request $request)
    {

        $user = Auth::user();

         $ownerId = $user->ownerId();

        $query = Unit::with('department')->where('user_id', $ownerId);
        if ($user->scopedDepartmentIds()) {
            $query->whereIn('department_id', $user->scopedDepartmentIds());
        }

        if ($request->filled('lecturer_id')) {
            $query->whereHas('lecturers', fn($q) => $q->where('lecturers.id', $request->lecturer_id));
        }

        $units = $query->paginate(10)->appends($request->all());

        $lecturers = Lecturer::where('user_id', $ownerId)->orderBy('name')->get(['id', 'name']);

        return view('units.index', compact('units', 'lecturers'));
    }

    /**
     * Show the form for creating a new Unit.
     */
    public function create()
    {
        $user    = Auth::user();
        $ownerId = $user->ownerId();
        $dq      = Department::where('user_id', $ownerId);
        if ($user->scopedDepartmentIds()) $dq->whereIn('id', $user->scopedDepartmentIds());
        $departments = $dq->pluck('name', 'id')->prepend('-- Select Department --', '');

        return view('units.create', compact('departments'));
    }

    /**
     * Store a newly created Unit in storage.
     */
    public function store(CreateUnitRequest $request)
    {
        $input = $this->withHourLimit($request);

        $user = Auth::user();

        $input['user_id'] = $user->ownerId();

        $unit = $this->unitRepository->create($input);

        Flash::success('Unit saved successfully.');

        return redirect(route('units.index'));
    }

    /**
     * Display the specified Unit.
     */
    public function show($id)
    {
        $unit = $this->unitRepository->find($id);

        if (empty($unit)) {
            Flash::error('Unit not found');

            return redirect(route('units.index'));
        }

        $unit->load('department');

        $lectureAdministereds = \App\Models\LectureAdministered::with(['lecturer', 'classs'])
            ->where('unit_id', $id)
            ->orderBy('lecture_date', 'desc')
            ->get();

        return view('units.show', compact('unit', 'lectureAdministereds'));
    }

    /**
     * Show the form for editing the specified Unit.
     */
    public function edit($id)
    {
        $unit = $this->unitRepository->find($id);

        if (empty($unit)) {
            Flash::error('Unit not found');

            return redirect(route('units.index'));
        }

        $user    = Auth::user();
        $ownerId = $user->ownerId();
        $dq      = Department::where('user_id', $ownerId);
        if ($user->scopedDepartmentIds()) $dq->whereIn('id', $user->scopedDepartmentIds());
        $departments = $dq->pluck('name', 'id')->prepend('-- Select Department --', '');

        return view('units.edit', compact('unit', 'departments'));
    }

    /**
     * Update the specified Unit in storage.
     */
    public function update($id, UpdateUnitRequest $request)
    {
        $unit = $this->unitRepository->find($id);

        if (empty($unit)) {
            Flash::error('Unit not found');

            return redirect(route('units.index'));
        }

        $unit = $this->unitRepository->update($this->withHourLimit($request), $id);

        Flash::success('Unit updated successfully.');

        return redirect(route('units.index'));
    }

    /** Request data with max_hours validated; only the superuser may set it. */
    private function withHourLimit(Request $request): array
    {
        if (!Auth::user()->isSuperAdmin()) {
            return $request->except('max_hours');
        }

        $request->validate(['max_hours' => 'nullable|numeric|min:0']);

        $input = $request->all();
        $input['max_hours'] = $request->filled('max_hours') ? $request->max_hours : null;

        return $input;
    }

    /**
     * Remove the specified Unit from storage.
     *
     * @throws \Exception
     */
    public function destroy($id)
    {
        $unit = $this->unitRepository->find($id);

        if (empty($unit)) {
            Flash::error('Unit not found');

            return redirect(route('units.index'));
        }

        $this->unitRepository->delete($id);

        Flash::success('Unit deleted successfully.');

        return redirect(route('units.index'));
    }
}
