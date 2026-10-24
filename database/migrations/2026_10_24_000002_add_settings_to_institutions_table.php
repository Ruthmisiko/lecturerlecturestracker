<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('owner_id');
            $table->string('letterhead_path')->nullable()->after('logo_path');
            $table->string('primary_color', 7)->default('#28a745')->after('letterhead_path');
            $table->string('secondary_color', 7)->default('#1e7e34')->after('primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'letterhead_path', 'primary_color', 'secondary_color']);
        });
    }
};
