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

        $users = User::whereIn('user_id', array_unique([$ownerId, $user->id]))
            ->with('roles', 'departments')
            ->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles       = Role::all();
        $departments = Department::where('user_id', Auth::user()->ownerId())->get();
        return view('users.create', compact('roles', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => 'required',
            ...$this->departmentRules(),
        ]);

        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'user_id'       => Auth::user()->ownerId(),
            'department_id' => null,
        ]);

        $user->assignRole($request->role);
        $user->departments()->sync($request->departments ?? []);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $user        = User::findOrFail($id);
        $roles       = Role::all();
        $departments = Department::where('user_id', Auth::user()->ownerId())->get();
        return view('users.edit', compact('user', 'roles', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->departmentRules());

        $user = User::findOrFail($id);

        $user->update([
            'name'          => $request->name,
            'email'         => $request->email,
            'department_id' => null,
        ]);

        $user->syncRoles([$request->role]);
        $user->departments()->sync($request->departments ?? []);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /** Departments may only be picked from the current account's own departments. */
    private function departmentRules(): array
    {
        return [
            'departments'   => 'nullable|array',
            'departments.*' => Rule::exists('departments', 'id')->where('user_id', Auth::user()->ownerId()),
        ];
    }
}
