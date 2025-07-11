<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
{
    $roles = Role::with('permissions')->get(); // eager load permissions
    return view('roles.index', compact('roles'));
}

public function create()
{
    $permissions = Permission::all();
    return view('roles.create', compact('permissions'));
}

public function store(Request $request)
{
    $role = Role::create(['name' => $request->name]);
    $role->syncPermissions($request->permissions ?? []);

    return redirect()->route('roles.index')->with('success', 'Role created successfully.');
}
public function edit($id)
{
    $role = Role::findOrFail($id);
    $permissions = Permission::all();

    return view('roles.edit', compact('role', 'permissions'));
}

public function update(Request $request, $id)
{
    $role = Role::findOrFail($id);

    $role->update(['name' => $request->name]);

    $role->syncPermissions($request->permissions ?? []);

    return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
}

}
