<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('department_lecturer')) {
            Schema::create('department_lecturer', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->foreignId('lecturer_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['department_id', 'lecturer_id']);
            });
        }

        // Carry over each lecturer's existing single department.
        DB::table('lecturers')->whereNotNull('department_id')->orderBy('id')->each(function ($lecturer) {
            DB::table('department_lecturer')->insertOrIgnore([
                'department_id' => $lecturer->department_id,
                'lecturer_id'   => $lecturer->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_lecturer');
    }
};
