<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->decimal('approved_hours', 10, 2)->nullable()->after('description');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->decimal('max_hours', 10, 2)->nullable()->after('unitCode');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('approved_hours');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('max_hours');
        });
    }
};
