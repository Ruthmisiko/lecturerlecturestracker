<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lecture_administereds', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lecture_administereds', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
};
