<?php

namespace App\Http\Controllers;

use App\Models\LectureAdministered;
use App\Models\Lecturer;
use App\Http\Requests\CreateLecturerRequest;
use App\Http\Requests\UpdateLecturerRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\LecturerRepository;
use Illuminate\Http\Request;
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
        $lecturers = $this->lecturerRepository->paginate(10);

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
