<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Google Gemini API key for reading attendance sheets, stored encrypted. */
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->text('gemini_api_key')->nullable()->after('employer_pin');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn('gemini_api_key');
        });
    }
};
