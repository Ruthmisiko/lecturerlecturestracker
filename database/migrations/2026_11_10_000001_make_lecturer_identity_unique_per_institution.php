<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The same person can teach at more than one institution, each keeping its own lecturer record,
 * so email and ID number only have to be unique within an institution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropUnique('lecturers_email_unique');
            $table->dropUnique('lecturers_id_number_unique');
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->unique(['institution_id', 'email'], 'lecturers_institution_email_unique');
            $table->unique(['institution_id', 'id_number'], 'lecturers_institution_id_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropUnique('lecturers_institution_email_unique');
            $table->dropUnique('lecturers_institution_id_number_unique');
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->unique('email');
            $table->unique('id_number');
        });
    }
};
