<?php

namespace App\Http\Controllers;

use App\Models\LectureAdministered;
use App\Models\Lecturer;
use App\Http\Requests\CreateLecturerRequest;
use App\Http\Requests\UpdateLecturerRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\LecturerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Flash;

class LecturerController extends AppBaseController
{
    /** @var LecturerRepository $lecturerRepository*/
    private $lecturerRepository;

    public function __construct(LecturerRepository $lecturerRepo)
    {
        $this->lecturerRepository = $lecturerRepo;
    }

    /**
     * Display a listing of the Lecturer.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $ownerId = $user->user_id ?? $user->id;


        $lecturers = Lecturer::where('user_id', $ownerId)->paginate(10);

        return view('lecturers.index')
            ->with('lecturers', $lecturers);
    }

    /**
     * Show the form for creating a new Lecturer.
     */
    public function create()
    {
        return view('lecturers.create');
    }

    /**
     * Store a newly created Lecturer in storage.
     */
    public function store(CreateLecturerRequest $request)
    {
        $input = $request->all();

        $user = Auth::user();

        $input['user_id'] = $user->user_id ?? $user->id;

        $lecturer = $this->lecturerRepository->create($input);

        Flash::success('Lecturer saved successfully.');

        return redirect(route('lecturers.index'));
    }

    /**
     * Display the specified Lecturer.
     */
    // public function show($id)
    // {
    //     $lecturer = $this->lecturerRepository->find($id);

    //     if (empty($lecturer)) {
    //         Flash::error('Lecturer not found');

    //         return redirect(route('lecturers.index'));
    //     }

    //     return view('lecturers.show')->with('lecturer', $lecturer);
    // }
    public function show($id)
    {
        $lecturer = Lecturer::with('lectureAdministereds.classs')->findOrFail($id);

        // Load ALL lectures for global clash checking
        $allLectures = LectureAdministered::with('lecturer', 'classs')->get();

        foreach ($lecturer->lectureAdministereds as $record) {

            // Default status
            $record->status = 'OK';

            // Own clash
            $ownClash = $lecturer->lectureAdministereds->filter(function ($r) use ($record) {
                return $r->id !== $record->id &&
                       $r->lecture_date == $record->lecture_date &&
                       $r->start_time == $record->start_time &&
                       $r->end_time == $record->end_time;
            });

            if ($ownClash->count() > 0) {
                $record->status = 'Own Clash';
            }

            // Clash with other lecturers
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

        return view('lecturers.show', compact('lecturer'));
    }

    /**
     * Show the form for editing the specified Lecturer.
     */
    public function edit($id)
    {
        $lecturer = $this->lecturerRepository->find($id);

        if (empty($lecturer)) {
            Flash::error('Lecturer not found');

            return redirect(route('lecturers.index'));
        }

        return view('lecturers.edit')->with('lecturer', $lecturer);
    }

    /**
     * Update the specified Lecturer in storage.
     */
    public function update($id, UpdateLecturerRequest $request)
    {
        $lecturer = $this->lecturerRepository->find($id);

        if (empty($lecturer)) {
            Flash::error('Lecturer not found');

            return redirect(route('lecturers.index'));
        }

        $lecturer = $this->lecturerRepository->update($request->all(), $id);

        Flash::success('Lecturer updated successfully.');

        return redirect(route('lecturers.index'));
    }

    /**
     * Remove the specified Lecturer from storage.
     *
     * @throws \Exception
     */
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
