<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bank details remembered per lecturer (ID number already exists).
        Schema::table('lecturers', function (Blueprint $table) {
            $table->string('account_number', 50)->nullable()->after('kra_pin');
            $table->string('bank_name', 100)->nullable()->after('account_number');
            $table->string('bank_branch', 100)->nullable()->after('bank_name');
        });

        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('schedule_date');
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->json('statuses')->nullable();
            $table->string('authorized_by_name')->nullable();
            $table->string('authorized_by_position')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->string('approved_by_position')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecturer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('name');
            $table->string('account_number', 50)->nullable();
            $table->decimal('amount', 14, 4)->default(0);
            $table->string('bank', 100)->nullable();
            $table->string('branch', 100)->nullable();
            $table->string('id_number', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_schedule_items');
        Schema::dropIfExists('payment_schedules');

        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropColumn(['account_number', 'bank_name', 'bank_branch']);
        });
    }
};
