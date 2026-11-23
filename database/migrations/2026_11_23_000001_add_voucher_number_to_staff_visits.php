<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_visits', function (Blueprint $table) {
            $table->string('voucher_number', 100)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('staff_visits', function (Blueprint $table) {
            $table->dropColumn('voucher_number');
        });
    }
};
