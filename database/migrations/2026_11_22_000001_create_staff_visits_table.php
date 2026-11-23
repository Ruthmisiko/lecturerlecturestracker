<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_id')->constrained('lecturers')->cascadeOnDelete();
            $table->string('type', 40)->default('visit');      // visit, exam_supervision, sports, other
            $table->string('purpose');
            $table->string('location')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();             // both null = away the whole day(s)
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('days')->default(1);
            $table->decimal('per_diem_rate', 10, 2)->default(0);
            $table->decimal('remuneration', 10, 2)->default(0);  // any extra pay on top of per diem
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');   // pending, paid
            $table->date('paid_on')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['lecturer_id', 'start_date', 'end_date']);
        });

        Permission::firstOrCreate(['name' => 'staff-visit.manage', 'guard_name' => 'web']);
        if ($superuser = Role::where('name', 'superuser')->first()) {
            $superuser->givePermissionTo('staff-visit.manage');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_visits');
        Permission::where('name', 'staff-visit.manage')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
