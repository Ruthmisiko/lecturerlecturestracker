<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('lecture_administereds', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->after('unit_id')->constrained('semesters')->nullOnDelete();
        });

        // Like institutions: every institution gets a "Default Semester" (marked current)
        // holding all of its existing lecture records. Rename it afterwards.
        foreach (DB::table('institutions')->get(['id', 'owner_id']) as $institution) {
            $semesterId = DB::table('semesters')->insertGetId([
                'name'           => 'Default Semester',
                'is_current'     => true,
                'user_id'        => $institution->owner_id,
                'institution_id' => $institution->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::table('lecture_administereds')
                ->where('institution_id', $institution->id)
                ->whereNull('semester_id')
                ->update(['semester_id' => $semesterId]);
        }
    }

    public function down(): void
    {
        Schema::table('lecture_administereds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('semester_id');
        });

        Schema::dropIfExists('semesters');
    }
};
