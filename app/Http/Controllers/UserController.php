<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $ownerId = $user->ownerId();

        $users = $this->institutionUsers()
            ->where('id', '!=', $ownerId)
            ->with('roles', 'departments')
            ->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create', $this->formData());
    }

    public function store(Request $request)
    {
        $institutionId = $this->chosenInstitutionId($request);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => 'required',
            ...$this->departmentRules($institutionId),
        ]);

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'user_id'        => Auth::user()->ownerId(),
            'institution_id' => $institutionId,
            'department_id'  => null,
        ]);

        $user->assignRole($request->role);
        $user->departments()->sync($request->departments ?? []);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $user = $this->institutionUsers()->findOrFail($id);

        return view('users.edit', ['user' => $user] + $this->formData());
    }

    public function update(Request $request, $id)
    {
        $user          = $this->institutionUsers()->findOrFail($id);
        $institutionId = $this->chosenInstitutionId($request, $user->institution_id);

        $request->validate($this->departmentRules($institutionId));

        $user->update([
            'name'           => $request->name,
            'email'          => $request->email,
            'institution_id' => $institutionId,
            'department_id'  => null,
        ]);

        $user->syncRoles([$request->role]);
        $user->departments()->sync($request->departments ?? []);

        $message = $institutionId === Auth::user()->currentInstitutionId()
            ? 'User updated successfully.'
            : 'User updated and moved to another institution.';

        return redirect()->route('users.index')->with('success', $message);
    }

    /** Roles, the institutions the current user may assign, and every department of those institutions. */
    private function formData(): array
    {
        $institutions = Auth::user()->accessibleInstitutions()->get();

        return [
            'roles'                => Role::all(),
            'institutions'         => $institutions,
            'currentInstitutionId' => Auth::user()->currentInstitutionId(),
            'canPickInstitution'   => Auth::user()->isSuperAdmin() && $institutions->count() > 1,
            'departments'          => Department::withoutGlobalScope('institution')
                ->whereIn('institution_id', $institutions->pluck('id'))
                ->orderBy('name')
                ->get(),
        ];
    }

    /** Superusers may place a user in any accessible institution; everyone else uses the current one. */
    private function chosenInstitutionId(Request $request, ?int $fallback = null): int
    {
        $current = $fallback ?? Auth::user()->currentInstitutionId();

        if (!Auth::user()->isSuperAdmin() || !$request->filled('institution_id')) {
            return $current;
        }

        $request->validate([
            'institution_id' => ['integer', Rule::in(Auth::user()->accessibleInstitutions()->pluck('id')->all())],
        ]);

        return (int) $request->institution_id;
    }

    /** Departments may only be picked from the chosen institution. */
    private function departmentRules(int $institutionId): array
    {
        return [
            'departments'   => 'nullable|array',
            'departments.*' => Rule::exists('departments', 'id')
                ->where('user_id', Auth::user()->ownerId())
                ->where('institution_id', $institutionId),
        ];
    }

    /** Users belonging to the institution currently being worked in. */
    private function institutionUsers()
    {
        return User::whereNotNull('user_id')
            ->where('institution_id', Auth::user()->currentInstitutionId());
    }
}
