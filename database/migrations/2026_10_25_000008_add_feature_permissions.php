<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** Permissions for the newer features, so the superuser can grant them to other roles. */
    private array $permissions = [
        'dashboard.view',
        'payment-report.view',
        'payslip.email',
        'payment-schedule.manage',
        'semester.manage',
        'email-settings.manage',
        'institution.manage',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        if ($superuser = Role::where('name', 'superuser')->first()) {
            $superuser->givePermissionTo($this->permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
