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
        Schema::table('lecturers', function (Blueprint $table) {
            $table->decimal('rate_per_hour', 10, 2)->default(0)->after('specialization');
            $table->decimal('tax_rate', 5, 2)->default(30)->after('rate_per_hour');
            $table->decimal('housing_levy_rate', 5, 2)->default(1.5)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropColumn(['rate_per_hour', 'tax_rate', 'housing_levy_rate']);
        });
    }
};
