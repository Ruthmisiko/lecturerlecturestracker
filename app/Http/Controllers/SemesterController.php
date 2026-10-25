<?php

namespace App\Http\Controllers;

use Flash;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SemesterController extends Controller
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
        $semesters = Semester::withCount('lectureAdministereds')
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        return view('semesters.index', compact('semesters'));
    }

    public function create()
    {
        return view('semesters.form', ['semester' => new Semester()]);
    }

    public function store(Request $request)
    {
        $semester = Semester::create($this->validated($request) + ['user_id' => Auth::user()->ownerId()]);

        if ($request->boolean('is_current') || !Semester::where('id', '!=', $semester->id)->where('is_current', true)->exists()) {
            $semester->makeCurrent();
        }

        Flash::success('Semester created.');

        return redirect()->route('semesters.index');
    }

    public function edit($id)
    {
        return view('semesters.form', ['semester' => Semester::findOrFail($id)]);
    }

    public function update(Request $request, $id)
    {
        $semester = Semester::findOrFail($id);
        $semester->update($this->validated($request));

        if ($request->boolean('is_current')) {
            $semester->makeCurrent();
        }

        Flash::success('Semester updated.');

        return redirect()->route('semesters.index');
    }

    public function makeCurrent($id)
    {
        $semester = Semester::findOrFail($id);
        $semester->makeCurrent();

        Flash::success($semester->name . ' is now the current semester. New lectures will be recorded under it.');

        return redirect()->route('semesters.index');
    }

    public function destroy($id)
    {
        $semester = Semester::withCount('lectureAdministereds')->findOrFail($id);

        if ($semester->lecture_administereds_count > 0) {
            Flash::error('This semester has ' . $semester->lecture_administereds_count . ' lecture record(s). Move or delete them before deleting the semester.');
            return redirect()->route('semesters.index');
        }

        $wasCurrent = $semester->is_current;
        $semester->delete();

        // Keep one semester current so new lectures always get one.
        if ($wasCurrent && ($next = Semester::orderByDesc('start_date')->orderByDesc('id')->first())) {
            $next->makeCurrent();
        }

        Flash::success('Semester deleted.');

        return redirect()->route('semesters.index');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        return $data;
    }
}
