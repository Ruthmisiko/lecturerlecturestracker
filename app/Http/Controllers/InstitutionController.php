<?php

namespace App\Http\Controllers;

use Flash;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

        Storage::disk('local')->deleteDirectory("institutions/{$institution->id}");
        $institution->delete();
        session()->forget('active_institution_id');

        Flash::success('Institution deleted.');

        return redirect()->route('institutions.index');
    }

    public function settings($id)
    {
        $this->authorizeSuperuser();

        return view('institutions.settings', ['institution' => $this->find($id)]);
    }

    public function updateSettings(Request $request, $id)
    {
        $this->authorizeSuperuser();
        $institution = $this->find($id);

        $request->validate([
            'logo'              => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'letterhead'        => 'nullable|image|mimes:png,jpg,jpeg|max:5120',
            'primary_color'     => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color'   => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'employer_pin'      => 'nullable|string|max:30',
        ], [
            'primary_color.regex'   => 'Pick a valid primary colour.',
            'secondary_color.regex' => 'Pick a valid secondary colour.',
            'logo.max'              => 'The logo must be 2 MB or smaller.',
            'letterhead.max'        => 'The letterhead must be 5 MB or smaller.',
        ]);

        $data = [
            'primary_color'   => strtolower($request->primary_color),
            'secondary_color' => strtolower($request->secondary_color),
            'employer_pin'    => $request->filled('employer_pin') ? strtoupper(trim($request->employer_pin)) : null,
        ];

        foreach (Institution::ASSETS as $type => $column) {
            $remove = $request->boolean("remove_{$type}");

            if (($request->hasFile($type) || $remove) && $institution->$column) {
                Storage::disk('local')->delete($institution->$column);
                $data[$column] = null;
            }

            if ($request->hasFile($type)) {
                try {
                    $path = $request->file($type)->store("institutions/{$institution->id}", 'local');
                } catch (\Throwable $e) {
                    report($e);
                    $path = false;
                }

                if (!$path) {
                    return back()->withInput()->withErrors([
                        $type => "Could not save the {$type} on the server. The storage folder is probably not writable "
                            . '(run: chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache).',
                    ]);
                }

                $data[$column] = $path;
            }
        }

        $institution->update($data);

        Flash::success('Settings saved for ' . $institution->name . '.');

        return redirect()->route('institutions.settings', $institution->id);
    }

    /** Streams an institution's logo or letterhead to anyone who can work in that institution. */
    public function asset($id, string $type)
    {
        $institution = $this->find($id);
        $path        = $institution->assetPath($type);

        abort_unless($path, 404);

        return response()->file($path, ['Cache-Control' => 'private, max-age=86400']);
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
