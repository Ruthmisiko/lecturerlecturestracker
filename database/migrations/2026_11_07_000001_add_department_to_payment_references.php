<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** A lecturer who taught in several departments gets a separate voucher number / transaction ref for each. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_references', function (Blueprint $table) {
            $table->unsignedBigInteger('department_key')->default(0)->after('lecturer_id');
        });

        // Existing references belong to the lecturer's main department.
        DB::statement('UPDATE payment_references SET department_key = COALESCE((SELECT department_id FROM lecturers WHERE lecturers.id = payment_references.lecturer_id), 0)');

        Schema::table('payment_references', function (Blueprint $table) {
            $table->dropUnique(['lecturer_id', 'period_key']);
            $table->unique(['lecturer_id', 'period_key', 'department_key'], 'payment_refs_lecturer_period_dept_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payment_references', function (Blueprint $table) {
            $table->dropUnique('payment_refs_lecturer_period_dept_unique');
        });

        // Keep one row per lecturer and period again.
        DB::statement('DELETE p1 FROM payment_references p1 JOIN payment_references p2 ON p1.lecturer_id = p2.lecturer_id AND p1.period_key = p2.period_key AND p1.id > p2.id');

        Schema::table('payment_references', function (Blueprint $table) {
            $table->dropColumn('department_key');
            $table->unique(['lecturer_id', 'period_key']);
        });
    }
};
