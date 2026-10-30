<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** New permission => the one that covered it before, so roles keep the access they had. */
    private array $permissions = [
        'bulk-email.send'       => 'payslip.email',
        'email-template.manage' => 'email-settings.manage',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name => $previous) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            Role::whereHas('permissions', fn ($q) => $q->where('name', $previous))->get()
                ->each(fn ($role) => $role->givePermissionTo($name));
        }

        // The superuser always has everything.
        if ($superuser = Role::where('name', 'superuser')->first()) {
            $superuser->givePermissionTo(array_keys($this->permissions));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_keys($this->permissions))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
