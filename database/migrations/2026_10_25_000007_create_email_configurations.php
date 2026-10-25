<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SMTP accounts payslips can be sent from (several per institution, one default).
        Schema::create('email_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('from_name');
            $table->string('from_address');
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(587);
            $table->string('encryption', 10)->nullable(); // tls | ssl | null
            $table->string('username')->nullable();
            $table->text('password')->nullable();          // encrypted
            $table->boolean('is_default')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->cascadeOnDelete();
            $table->timestamps();
        });

        // A record of every payslip emailed.
        Schema::create('payslip_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecturer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('email_configuration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to');
            $table->string('subject');
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_emails');
        Schema::dropIfExists('email_configurations');
    }
};
