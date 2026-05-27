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
        $user    = Auth::user();
        $ownerId = $user->user_id ?? $user->id;

        $query = Lecturer::where('user_id', $ownerId)->with('department');

        if ($user->scopedDepartmentId()) {
            $query->where('department_id', $user->scopedDepartmentId());
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $lecturers = $query->paginate(10)->withQueryString();

        $departments = $user->isSuperAdmin()
            ? Department::orderBy('name')->get()
            : Department::where('user_id', $ownerId)->orderBy('name')->get();

        return view('lecturers.index', compact('lecturers', 'departments'));
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
        $lecturer = Lecturer::with([
            'lectureAdministereds.classs',
            'lectureAdministereds.unit',
            'department',
            'units',
        ])->findOrFail($id);

        // All records for this lecturer — used for own-clash detection
        $allOwn = $lecturer->lectureAdministereds;

        // All OTHER lecturers' records — used for clash-with detection
        $allOthers = LectureAdministered::with('lecturer', 'classs')
            ->where('lecturer_id', '!=', $lecturer->id)
            ->get();

        // Compute clash status on every record using proper time-overlap logic
        foreach ($allOwn as $record) {
            $ownClash = $allOwn->contains(fn($r) =>
                $r->id !== $record->id &&
                $r->lecture_date === $record->lecture_date &&
                !($record->end_time <= $r->start_time || $record->start_time >= $r->end_time)
            );

            $otherClash = $allOthers->filter(fn($r) =>
                $r->classs_id === $record->classs_id &&
                $r->lecture_date === $record->lecture_date &&
                !($record->end_time <= $r->start_time || $record->start_time >= $r->end_time)
            );

            if ($ownClash && $otherClash->isNotEmpty()) {
                $record->computed_status   = 'both';
                $record->clash_with_names  = $otherClash->pluck('lecturer.name')->filter()->unique()->join(', ');
            } elseif ($ownClash) {
                $record->computed_status   = 'own_clash';
                $record->clash_with_names  = null;
            } elseif ($otherClash->isNotEmpty()) {
                $record->computed_status   = 'clash_with';
                $record->clash_with_names  = $otherClash->pluck('lecturer.name')->filter()->unique()->join(', ');
            } else {
                $record->computed_status   = 'ok';
                $record->clash_with_names  = null;
            }
        }

        // Apply filters
        $lectures = $this->applyLecturerShowFilters($allOwn, $request);

        $lecturerUnits = $lecturer->units;

        return view('lecturers.show', compact('lecturer', 'lectures', 'lecturerUnits'));
    }

    private function applyLecturerShowFilters($records, Request $request)
    {
        if ($request->filled('from_date')) {
            $records = $records->filter(fn($r) => $r->lecture_date >= $request->from_date);
        }
        if ($request->filled('to_date')) {
            $records = $records->filter(fn($r) => $r->lecture_date <= $request->to_date);
        }
        if ($request->filled('class')) {
            $records = $records->filter(fn($r) =>
                str_contains(strtolower($r->classs->name ?? ''), strtolower($request->class))
            );
        }
        if ($request->filled('unit_id')) {
            $records = $records->filter(fn($r) => $r->unit_id == $request->unit_id);
        }
        if ($request->filled('status')) {
            $records = $records->filter(fn($r) => match($request->status) {
                'own_clash'  => in_array($r->computed_status, ['own_clash', 'both']),
                'clash_with' => in_array($r->computed_status, ['clash_with', 'both']),
                'ok'         => $r->computed_status === 'ok',
                default      => true,
            });
        }
        return $records->values();
    }

    public function exportPdf(Request $request, $id)
    {
        $lecturer = Lecturer::with([
            'lectureAdministereds.classs',
            'lectureAdministereds.unit',
            'department',
            'units',
        ])->findOrFail($id);

        $allOwn    = $lecturer->lectureAdministereds;
        $allOthers = LectureAdministered::with('lecturer', 'classs')
            ->where('lecturer_id', '!=', $lecturer->id)->get();

        foreach ($allOwn as $record) {
            $ownClash   = $allOwn->contains(fn($r) =>
                $r->id !== $record->id &&
                $r->lecture_date === $record->lecture_date &&
                !($record->end_time <= $r->start_time || $record->start_time >= $r->end_time)
            );
            $otherClash = $allOthers->filter(fn($r) =>
                $r->classs_id === $record->classs_id &&
                $r->lecture_date === $record->lecture_date &&
                !($record->end_time <= $r->start_time || $record->start_time >= $r->end_time)
            );
            $record->computed_status  = $ownClash && $otherClash->isNotEmpty() ? 'both'
                : ($ownClash ? 'own_clash' : ($otherClash->isNotEmpty() ? 'clash_with' : 'ok'));
            $record->clash_with_names = $otherClash->pluck('lecturer.name')->filter()->unique()->join(', ');
        }

        $lectures = $this->applyLecturerShowFilters($allOwn, $request);

        $pdf = PDF::loadView('lecturers.export_pdf', compact('lecturer', 'lectures'))
                  ->setPaper('a4', 'landscape');
        return $pdf->download('lecturer_' . $lecturer->id . '_lectures.pdf');
    }

    public function exportExcel(Request $request, $id)
    {
        $lecturer = Lecturer::with([
            'lectureAdministereds.classs',
            'lectureAdministereds.unit',
            'department',
            'units',
        ])->findOrFail($id);

        $allOwn    = $lecturer->lectureAdministereds;
        $allOthers = LectureAdministered::with('lecturer', 'classs')
            ->where('lecturer_id', '!=', $lecturer->id)->get();

        foreach ($allOwn as $record) {
            $ownClash   = $allOwn->contains(fn($r) =>
                $r->id !== $record->id &&
                $r->lecture_date === $record->lecture_date &&
                !($record->end_time <= $r->start_time || $record->start_time >= $r->end_time)
            );
            $otherClash = $allOthers->filter(fn($r) =>
                $r->classs_id === $record->classs_id &&
                $r->lecture_date === $record->lecture_date &&
                !($record->end_time <= $r->start_time || $record->start_time >= $r->end_time)
            );
            $record->computed_status  = $ownClash && $otherClash->isNotEmpty() ? 'both'
                : ($ownClash ? 'own_clash' : ($otherClash->isNotEmpty() ? 'clash_with' : 'ok'));
            $record->clash_with_names = $otherClash->pluck('lecturer.name')->filter()->unique()->join(', ');
        }

        $lectures = $this->applyLecturerShowFilters($allOwn, $request);

        $rows = $lectures->map(fn($r) => [
            'Class'        => $r->classs->name ?? '-',
            'Unit'         => $r->unit->name ?? '-',
            'Lecture Date' => $r->lecture_date,
            'Start Time'   => $r->start_time,
            'End Time'     => $r->end_time,
            'Status'       => match($r->computed_status) {
                'own_clash'  => 'Own Clash',
                'clash_with' => 'Clash with: ' . $r->clash_with_names,
                'both'       => 'Own Clash & Clash with: ' . $r->clash_with_names,
                default      => 'OK',
            },
        ]);

        return Excel::download(new \App\Exports\CollectionExport(
            collect([['Class','Unit','Lecture Date','Start Time','End Time','Status']])->merge($rows->toArray())
        ), 'lecturer_' . $lecturer->id . '_lectures.xlsx');
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
