<?php

namespace App\Http\Controllers;

use PDF;
use Flash;
use Illuminate\Http\Request;
use App\Exports\LectureExport;
use App\Imports\LectureImport;
use App\Models\LectureAdministered;
use Illuminate\Support\Facades\Auth;
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

    $user = Auth::user();

    $ownerId = $user->user_id ?? $user->id;

   $query = LectureAdministered::with(['lecturer', 'classs'])
    ->where('user_id', $ownerId);


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


       $duplicates = LectureAdministered::where('user_id', Auth::id())
            ->select('lecturer_id', 'classs_id', 'lecture_date', 'start_time', 'end_time')
            ->groupBy('lecturer_id', 'classs_id', 'lecture_date', 'start_time', 'end_time')
            ->havingRaw('COUNT(*) > 1')
            ->with(['lecturer' => function ($q) {
                $q->where('user_id', Auth::id());
            }, 'classs' => function ($q) {
                $q->where('user_id', Auth::id());
            }])
            ->get();

        // $clashes = LectureAdministered::where('user_id', Auth::id())
        //     ->select('classs_id', 'lecture_date', 'start_time', 'end_time')
        //     ->groupBy('classs_id', 'lecture_date', 'start_time', 'end_time')
        //     ->havingRaw('COUNT(DISTINCT lecturer_id) > 1')
        //     ->with(['classs' => function ($q) {
        //         $q->where('user_id', Auth::id());
        //     }])
        //     ->get();
        $clashes = LectureAdministered::where('user_id', Auth::id())
        ->with(['lecturer', 'classs'])
        ->get()
        ->groupBy(function ($item) {
            return $item->classs_id . '_' . $item->lecture_date . '_' . $item->start_time . '_' . $item->end_time;
        })
        ->filter(function ($group) {
            return $group->count() > 1; // only groups with multiple lecturers
        })
        ->flatMap(function ($group) {
            return $group; // flatten so each item is a model, not a collection
        });

        if ($request->filled('status')) {

            $lectureAdministereds = $query->get(); // temporary fetch whole list to evaluate status

            $filtered = $lectureAdministereds->filter(function ($item) use ($clashes, $request) {

                $ownClash = $clashes->contains(function ($c) use ($item) {
                    return $c->id != $item->id &&
                           $c->lecturer_id == $item->lecturer_id &&
                           $c->lecture_date == $item->lecture_date &&
                           $c->start_time == $item->start_time &&
                           $c->end_time == $item->end_time;
                           $c->status == $item->status;
                });

                $clashWith = $clashes->contains(function ($c) use ($item) {
                    return $c->id != $item->id &&
                           $c->classs_id == $item->classs_id &&
                           $c->lecture_date == $item->lecture_date &&
                           $c->lecturer_id != $item->lecturer_id &&
                           $c->status == $item->status &&
                           !(
                               $item->end_time <= $c->start_time ||
                               $item->start_time >= $c->end_time
                           );
                });

                $ok = !$ownClash && !$clashWith;

                return match ($request->status) {
                    'own_clash' => $ownClash,
                    'clash_with' => $clashWith,
                    'ok' => $ok,
                };
            });

            // manual pagination for filtered results
            $lectureAdministereds = new \Illuminate\Pagination\LengthAwarePaginator(
                $filtered->forPage(\request()->get('page', 1), 10),
                $filtered->count(),
                10,
                \request()->get('page', 1)
            );
        }

    return view('lecture_administereds.index', compact('lectureAdministereds', 'duplicates','clashes'))
        ->with('lectureAdministereds', $lectureAdministereds);
}

public function exportExcel(Request $request)
{
    $data = $this->index($request)->getData()['lectureAdministereds'];

    return Excel::download(new LectureExport($data), 'lecture_data.xlsx');
}

public function exportPdf(Request $request)
{
    $data = $this->index($request)->getData()['lectureAdministereds'];

    $pdf = PDF::loadView('lecture_administereds.pdf', ['data' => $data]);

    return $pdf->download('lecture_data.pdf');
}

    /**
     * Show the form for creating a new LectureAdministered.
     */
    public function create()
    {
       $lecturers = \App\Models\Lecturer::where('user_id', Auth::id())->pluck('name', 'id');
    $classes = \App\Models\Classs::where('user_id', Auth::id())->pluck('name', 'id');
    return view('lecture_administereds.create', compact('lecturers', 'classes'));
    }

    /**
     * Store a newly created LectureAdministered in storage.
     */
  public function store(Request $request)
    {
        $request->validate([
          'lecturer_id' => 'required|exists:lecturers,id,user_id,' . Auth::id(),
            'classs_id' => 'required|exists:classses,id,user_id,' . Auth::id(),
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'lecture_dates' => 'required|array',
            'lecture_dates.*' => 'date',
        ]);
        $user = Auth::user();

        $ownerId = $user->user_id ?? $user->id;

        foreach ($request->lecture_dates as $date) {
            LectureAdministered::create([
                 'user_id' => $ownerId,
                'lecturer_id' => $request->lecturer_id,
                'classs_id' => $request->classs_id,
                'lecture_date' => $date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
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
