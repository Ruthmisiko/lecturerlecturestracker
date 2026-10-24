<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Payable clash statuses chosen for each row (null = all statuses). */
    public function up(): void
    {
        Schema::table('payment_schedule_items', function (Blueprint $table) {
            $table->json('statuses')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_schedule_items', function (Blueprint $table) {
            $table->dropColumn('statuses');
        });
    }
};
