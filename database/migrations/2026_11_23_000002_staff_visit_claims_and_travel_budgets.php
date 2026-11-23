<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            $table->string('personal_number', 50)->nullable()->after('id_number');
            $table->string('job_group', 50)->nullable()->after('personal_number');
            $table->decimal('per_diem_rate', 10, 2)->default(0)->after('rate_per_hour');
        });

        Schema::table('departments', function (Blueprint $table) {
            // Yearly travel allocation. A department can instead draw on another one's budget
            // (e.g. Accounts and Procurement under Administration).
            $table->decimal('travel_budget', 14, 2)->nullable()->after('approved_hours');
            $table->unsignedBigInteger('travel_budget_parent_id')->nullable()->after('travel_budget');
        });

        Schema::table('staff_visits', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('lecturer_id')->constrained('departments')->nullOnDelete();
            $table->date('date_of_entry')->nullable()->after('department_id');
            $table->string('sponsor')->nullable()->after('location');       // "who sponsored the activity"
            $table->string('itinerary')->nullable()->after('sponsor');
            $table->text('narration')->nullable()->after('purpose');
            $table->boolean('days_manual')->default(false)->after('days');   // days typed in, not worked out from dates
            $table->decimal('fare', 10, 2)->default(0)->after('per_diem_rate');
            $table->decimal('meals', 10, 2)->default(0)->after('fare');
        });
    }

    public function down(): void
    {
        Schema::table('staff_visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['date_of_entry', 'sponsor', 'itinerary', 'narration', 'days_manual', 'fare', 'meals']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['travel_budget', 'travel_budget_parent_id']);
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropColumn(['personal_number', 'job_group', 'per_diem_rate']);
        });
    }
};
