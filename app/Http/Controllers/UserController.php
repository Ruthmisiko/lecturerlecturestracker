<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $ownerId = $user->user_id ?? $user->id;

        $users = User::where('user_id', $ownerId)->with('roles', 'department')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles       = Role::all();
        $departments = Department::where('user_id', Auth::id())->orWhere('user_id', Auth::user()->user_id ?? Auth::id())->get();
        return view('users.create', compact('roles', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => 'required',
        ]);

        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'user_id'       => Auth::id(),
            'department_id' => $request->department_id ?: null,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $user        = User::findOrFail($id);
        $roles       = Role::all();
        $departments = Department::where('user_id', Auth::id())->orWhere('user_id', Auth::user()->user_id ?? Auth::id())->get();
        return view('users.edit', compact('user', 'roles', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'name'          => $request->name,
            'email'         => $request->email,
            'department_id' => $request->department_id ?: null,
        ]);

        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }
}
