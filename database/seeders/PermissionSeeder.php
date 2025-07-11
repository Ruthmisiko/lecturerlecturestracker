<?php

namespace Database\Seeders;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'user.create', 'user.edit', 'user.delete', 'user.view', 'user.manage',
            'class.create', 'class.edit', 'class.delete', 'class.view', 'class.manage',
            'lecturer.create', 'lecturer.edit', 'lecturer.delete', 'lecturer.view', 'lecturer.manage',
            'lecture.create', 'lecture.edit', 'lecture.delete', 'lecture.view', 'lecture.manage',
            'role.create', 'role.edit', 'role.delete', 'role.view', 'role.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superuser = Role::firstOrCreate(['name' => 'superuser']);
        $superuser->syncPermissions(Permission::all());
    }
}
