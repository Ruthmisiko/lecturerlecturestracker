<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_references', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id')->nullable()->index();
            $table->unsignedBigInteger('lecturer_id')->index();
            $table->string('period_key', 30); // "from|to", empty parts for an open-ended period
            $table->string('pv_no', 50)->nullable();
            $table->string('transaction_ref', 100)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->unique(['lecturer_id', 'period_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_references');
    }
};
