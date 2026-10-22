<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('department_user')) {
            Schema::create('department_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['department_id', 'user_id']);
            });
        }

        // Carry over each user's existing single department assignment.
        DB::table('users')->whereNotNull('department_id')->orderBy('id')->each(function ($user) {
            DB::table('department_user')->insertOrIgnore([
                'department_id' => $user->department_id,
                'user_id'       => $user->id,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_user');
    }
};
