<?php

namespace App\Http\Controllers;

use App\Models\LectureAdministered;
use App\Http\Requests\CreateLectureAdministeredRequest;
use App\Http\Requests\UpdateLectureAdministeredRequest;
use App\Http\Controllers\AppBaseController;
use App\Repositories\LectureAdministeredRepository;
use Illuminate\Http\Request;
use Flash;

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
    $query = LectureAdministered::with(['lecturer', 'classs']);

    if ($request->filled('lecturer')) {
        $query->whereHas('lecturer', function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->lecturer . '%');
        });
    }

    if ($request->filled('class')) {
        $query->whereHas('classs', function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->class . '%');
        });
    }

    if ($request->filled('lecture_date')) {
        $query->where('lecture_date', $request->lecture_date);
    }

    $lectureAdministereds = $query->paginate(10)->appends($request->all());

    $duplicates = LectureAdministered::select('lecturer_id', 'classs_id', 'lecture_date')
        ->groupBy('lecturer_id', 'classs_id', 'lecture_date')
        ->havingRaw('COUNT(*) > 1')
        ->with(['lecturer', 'classs'])
        ->get(); 

    return view('lecture_administereds.index', compact('lectureAdministereds', 'duplicates'))
        ->with('lectureAdministereds', $lectureAdministereds);
}


    /**
     * Show the form for creating a new LectureAdministered.
     */
    public function create()
    {
        return view('lecture_administereds.create');
    }

    /**
     * Store a newly created LectureAdministered in storage.
     */
   public function store(Request $request)
{
    $request->validate([
        'lecturer_id' => 'required|exists:lecturers,id',
        'classs_id' => 'required|exists:classses,id',
        'lecture_time' => 'required',
        'lecture_dates' => 'required|array',
        'lecture_dates.*' => 'date',
    ]);

    foreach ($request->lecture_dates as $date) {
        LectureAdministered::create([
            'lecturer_id' => $request->lecturer_id,
            'classs_id' => $request->classs_id,
            'lecture_date' => $date,
            'lecture_time' => $request->lecture_time,
        ]);
    }

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

        if (empty($lectureAdministered)) {
            Flash::error('Lecture Administered not found');

            return redirect(route('lectureAdministereds.index'));
        }

        return view('lecture_administereds.edit')->with('lectureAdministered', $lectureAdministered);
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
        'lecture_time' => 'required'
    ]);

    $lectureAdministered = $this->lectureAdministeredRepository->find($id);

    if (empty($lectureAdministered)) {
        Flash::error('Lecture Administered not found');
        return redirect(route('lectureAdministereds.index'));
    }

    $lectureAdministered->update([
        'lecturer_id' => $request->lecturer_id,
        'classs_id' => $request->classs_id,
        'lecture_date' => $request->lecture_date,
        'lecture_time' => $request->lecture_time,
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
}
