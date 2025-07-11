<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
{
    $users = User::where('user_id', Auth::id())->with('roles')->get();
    return view('users.index', compact('users'));
}

public function create()
{
    $roles = Role::all();
    return view('users.create', compact('roles'));
}

public function store(Request $request)
{
    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
        'user_id'  => Auth::id(), // <- important
    ]);

    $user->assignRole($request->role);

    return redirect()->route('users.index')->with('success', 'User created successfully.');
}

public function edit($id)
{
    $user  = User::findOrFail($id);
    $roles = Role::all();

    return view('users.edit', compact('user', 'roles'));
}

public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $user->update([
        'name'  => $request->name,
        'email' => $request->email,
    ]);

    $user->syncRoles([$request->role]);

    return redirect()->route('users.index')->with('success', 'User updated successfully.');
}

}
