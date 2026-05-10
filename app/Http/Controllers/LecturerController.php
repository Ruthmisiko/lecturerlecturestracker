<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LectureAdministered;
use App\Models\Lecturer;
use App\Models\Unit;
use App\Http\Requests\UpdateLecturerRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\LecturerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Flash;

class LecturerController extends AppBaseController
{
    private $lecturerRepository;

    public function __construct(LecturerRepository $lecturerRepo)
    {
        $this->lecturerRepository = $lecturerRepo;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $ownerId = $user->user_id ?? $user->id;

        $query = Lecturer::where('user_id', $ownerId)->with('department');
        if ($user->scopedDepartmentId()) {
            $query->where('department_id', $user->scopedDepartmentId());
        }
        $lecturers = $query->paginate(10);

        return view('lecturers.index')->with('lecturers', $lecturers);
    }

    public function create()
    {
        $user = Auth::user();
        $ownerId = $user->user_id ?? $user->id;

        $deptScope   = $user->scopedDepartmentId();
        $deptQuery   = Department::where('user_id', $ownerId);
        if ($deptScope) $deptQuery->where('id', $deptScope);
        $departments = $deptQuery->get();

        $unitsQuery = Unit::where('user_id', $ownerId);
        if ($deptScope) $unitsQuery->where('department_id', $deptScope);
        $units = $unitsQuery->get();

        return view('lecturers.create', compact('departments', 'units'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $ownerId = $user->user_id ?? $user->id;

        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'lecturers' => 'required|array|min:1',
            'lecturers.*.name' => 'required|string|max:255',
            'lecturers.*.email' => 'nullable|email|max:255',
            'lecturers.*.phone' => 'nullable|string|max:50',
            'lecturers.*.id_number' => 'nullable|string|max:50',
            'lecturers.*.kra_pin' => 'nullable|string|max:50',
            'lecturers.*.specialization' => 'nullable|string|max:255',
        ]);

        $departmentId = $request->department_id;

        DB::transaction(function () use ($request, $ownerId, $departmentId) {
            foreach ($request->lecturers as $data) {
                $lecturer = Lecturer::create([
                    'user_id'        => $ownerId,
                    'department_id'  => $departmentId,
                    'name'           => $data['name'],
                    'email'          => $data['email'] ?? null,
                    'phone'          => $data['phone'] ?? null,
                    'id_number'      => $data['id_number'] ?? null,
                    'kra_pin'        => $data['kra_pin'] ?? null,
                    'specialization' => $data['specialization'] ?? null,
                ]);

                if (!empty($data['units'])) {
                    $lecturer->units()->sync($data['units']);
                }
            }
        });

        Flash::success('Lecturer(s) saved successfully.');

        return redirect(route('lecturers.index'));
    }

    public function show(Request $request, $id)
    {
        $lecturer = Lecturer::with('lectureAdministereds.classs', 'department', 'units')->findOrFail($id);

        $from = $request->from_date;
        $to   = $request->to_date;

        $lectures = $lecturer->lectureAdministereds;

        if ($from) {
            $lectures = $lectures->filter(fn($rec) => $rec->lecture_date >= $from);
        }
        if ($to) {
            $lectures = $lectures->filter(fn($rec) => $rec->lecture_date <= $to);
        }

        $lecturer->lectureAdministereds = $lectures->values();

        $allLectures = LectureAdministered::with('lecturer', 'classs')->get();

        foreach ($lecturer->lectureAdministereds as $record) {
            $record->status = 'OK';

            $ownClash = $lecturer->lectureAdministereds->filter(function ($r) use ($record) {
                return $r->id !== $record->id &&
                       $r->lecture_date == $record->lecture_date &&
                       $r->start_time == $record->start_time &&
                       $r->end_time == $record->end_time;
            });

            if ($ownClash->count() > 0) {
                $record->status = 'Own Clash';
            }

            $otherClash = $allLectures->filter(function ($r) use ($record, $lecturer) {
                return $r->lecturer_id !== $lecturer->id &&
                       $r->lecture_date == $record->lecture_date &&
                       $r->start_time == $record->start_time &&
                       $r->end_time == $record->end_time &&
                       $r->class_id == $record->class_id;
            });

            if ($otherClash->count() > 0) {
                $names = $otherClash->pluck('lecturer.name')->unique()->join(', ');
                $record->status = $record->status === 'Own Clash'
                    ? 'Own Clash & Clash with ' . $names
                    : 'Clash with ' . $names;
            }
        }

        return view('lecturers.show', compact('lecturer', 'from', 'to'));
    }

    public function edit($id)
    {
        $lecturer = $this->lecturerRepository->find($id);

        if (empty($lecturer)) {
            Flash::error('Lecturer not found');
            return redirect(route('lecturers.index'));
        }

        $user = Auth::user();
        $ownerId = $user->user_id ?? $user->id;

        $departments = Department::where('user_id', $ownerId)->pluck('name', 'id')->prepend('-- Select Department --', '');
        $units = Unit::where('user_id', $ownerId)->pluck('name', 'id');
        $selectedUnits = $lecturer->units()->pluck('units.id')->toArray();

        return view('lecturers.edit', compact('lecturer', 'departments', 'units', 'selectedUnits'));
    }

    public function update($id, UpdateLecturerRequest $request)
    {
        $lecturer = $this->lecturerRepository->find($id);

        if (empty($lecturer)) {
            Flash::error('Lecturer not found');
            return redirect(route('lecturers.index'));
        }

        $data = $request->except('units');
        $lecturer = $this->lecturerRepository->update($data, $id);

        $lecturer->units()->sync($request->input('units', []));

        Flash::success('Lecturer updated successfully.');

        return redirect(route('lecturers.index'));
    }

    public function destroy($id)
    {
        $lecturer = $this->lecturerRepository->find($id);

        if (empty($lecturer)) {
            Flash::error('Lecturer not found');
            return redirect(route('lecturers.index'));
        }

        $this->lecturerRepository->delete($id);

        Flash::success('Lecturer deleted successfully.');

        return redirect(route('lecturers.index'));
    }
}
