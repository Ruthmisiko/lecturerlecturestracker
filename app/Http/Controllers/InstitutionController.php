<?php

namespace App\Http\Controllers;

use Flash;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstitutionController extends Controller
{
    /** Related models are normally limited to the current institution; counts here need every institution. */
    private const UNSCOPED = ['departments', 'lecturers', 'units'];

    public function index()
    {
        $this->authorizeSuperuser();

        $counts = [];
        foreach (self::UNSCOPED as $relation) {
            $counts[$relation] = fn ($q) => $q->withoutGlobalScope('institution');
        }

        $institutions = Auth::user()->accessibleInstitutions()->withCount($counts + ['users' => fn ($q) => $q])->get();
        $currentId    = Auth::user()->currentInstitutionId();

        return view('institutions.index', compact('institutions', 'currentId'));
    }

    public function create()
    {
        $this->authorizeSuperuser();

        return view('institutions.form', ['institution' => new Institution()]);
    }

    public function store(Request $request)
    {
        $this->authorizeSuperuser();
        $request->validate(['name' => 'required|string|max:255']);

        $institution = Institution::create([
            'name'     => $request->name,
            'owner_id' => Auth::user()->ownerId(),
        ]);

        if ($request->boolean('switch')) {
            $this->activate($institution->id);
        }

        Flash::success('Institution created.');

        return redirect()->route('institutions.index');
    }

    public function edit($id)
    {
        $this->authorizeSuperuser();

        return view('institutions.form', ['institution' => $this->find($id)]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeSuperuser();
        $request->validate(['name' => 'required|string|max:255']);

        $this->find($id)->update(['name' => $request->name]);

        Flash::success('Institution updated.');

        return redirect()->route('institutions.index');
    }

    public function destroy($id)
    {
        $this->authorizeSuperuser();
        $institution = $this->find($id);

        $hasData = $institution->users()->exists()
            || collect(self::UNSCOPED)->contains(fn ($r) => $institution->$r()->withoutGlobalScope('institution')->exists());

        if ($hasData) {
            Flash::error('Only empty institutions can be deleted. Remove its users, departments, lecturers and units first.');
            return redirect()->route('institutions.index');
        }

        if (Auth::user()->accessibleInstitutions()->count() <= 1) {
            Flash::error('You must keep at least one institution.');
            return redirect()->route('institutions.index');
        }

        $institution->delete();
        session()->forget('active_institution_id');

        Flash::success('Institution deleted.');

        return redirect()->route('institutions.index');
    }

    public function switch(Request $request)
    {
        $id = (int) $request->input('institution_id');

        if (!Auth::user()->accessibleInstitutions()->where('id', $id)->exists()) {
            return redirect()->back()->with('error', 'Invalid institution selection.');
        }

        $this->activate($id);

        return redirect()->route(Auth::user()->isSuperAdmin() ? 'dashboard' : 'lecturers.index');
    }

    private function activate(int $id): void
    {
        session(['active_institution_id' => $id]);
        // Department selections belong to the previous institution.
        session()->forget('active_department_id');
    }

    private function find($id): Institution
    {
        return Auth::user()->accessibleInstitutions()->findOrFail($id);
    }

    private function authorizeSuperuser(): void
    {
        abort_unless(Auth::user()->isSuperAdmin(), 403);
    }
}
