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
            ->with([
                'roles',
                'institution',
                'departments' => fn ($q) => $q->withoutGlobalScope('institution'),
            ])
            ->orderBy('institution_id')
            ->orderBy('name')
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

        $message = 'User created successfully.';
        if ($request->boolean('send_login')) {
            $message .= ' ' . $this->sendLoginDetails($user, $request->password, $request->role, $institutionId);
        }

        return redirect()->route('users.index')->with('success', $message);
    }

    public function edit($id)
    {
        $user = $this->institutionUsers()->findOrFail($id);

        return view('users.edit', ['user' => $user] + $this->formData());
    }

    public function update(Request $request, $id)
    {
        $user          = $this->institutionUsers()->findOrFail($id);
        $previousId    = $user->institution_id;
        $institutionId = $this->chosenInstitutionId($request, $previousId);

        $request->validate($this->departmentRules($institutionId));

        $user->update([
            'name'           => $request->name,
            'email'          => $request->email,
            'institution_id' => $institutionId,
            'department_id'  => null,
        ]);

        $user->syncRoles([$request->role]);
        $user->departments()->sync($request->departments ?? []);

        $message = $institutionId === $previousId
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
            'canPickInstitution'   => Auth::user()->isSuperAdmin(),
            'departments'          => Department::withoutGlobalScope('institution')
                ->whereIn('institution_id', $institutions->pluck('id'))
                ->orderBy('name')
                ->get(),
        ];
    }

    /**
     * Emails the new user their login details through the institution's default email account.
     * Returns a sentence describing the outcome (the user is created either way).
     */
    private function sendLoginDetails(User $user, string $password, string $role, int $institutionId): string
    {
        $institution = \App\Models\Institution::find($institutionId);
        $account = \App\Models\EmailConfiguration::withoutGlobalScope('institution')
            ->where('institution_id', $institutionId)
            ->orderByDesc('is_default')->orderBy('id')->first();

        if (!$account) {
            return 'Login details were NOT emailed: add an email account under Email Settings first.';
        }

        $theme = \App\Support\Theme::for($institution);

        try {
            $account->mailer()->to($user->email)->send(new \App\Mail\WelcomeUserMail(
                $user->name, $user->email, $password, $role,
                $institution->name ?? 'Lecturer Tracker',
                route('login'),
                $theme->primary, $theme->primaryText(),
                $account->from_address, $account->from_name,
            ));
        } catch (\Throwable $e) {
            report($e);
            return 'Login details could NOT be emailed: ' . $e->getMessage();
        }

        return "Login details were emailed to {$user->email}.";
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

    /** Users a manager can see: superusers see every institution's users, others only the current institution's. */
    private function institutionUsers()
    {
        $institutionIds = Auth::user()->isSuperAdmin()
            ? Auth::user()->accessibleInstitutions()->pluck('id')
            : [Auth::user()->currentInstitutionId()];

        return User::whereNotNull('user_id')->whereIn('institution_id', $institutionIds);
    }
}
