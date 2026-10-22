<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give lecturers that never had a rate set the standard 1000 KES per hour.
     */
    public function up(): void
    {
        DB::table('lecturers')
            ->where(fn ($q) => $q->whereNull('rate_per_hour')->orWhere('rate_per_hour', 0))
            ->update(['rate_per_hour' => 1000]);
    }

    public function down(): void
    {
        //
    }
};
