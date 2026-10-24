<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tables whose rows belong to an institution. */
    private array $dataTables = ['departments', 'lecturers', 'units', 'classses', 'lecture_administereds'];

    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        foreach (array_merge($this->dataTables, ['users']) as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            });
        }

        // Every top-level account gets a "Main Institution" holding all its existing data.
        $users   = DB::table('users')->pluck('user_id', 'id');
        $rootOf  = function ($id) use ($users) {
            $seen = [];
            while ($users->get($id) && !in_array($id, $seen) && $users->has($users->get($id))) {
                $seen[] = $id;
                $id = $users->get($id);
            }
            return $id;
        };

        $owners = collect($this->dataTables)
            ->flatMap(fn ($t) => DB::table($t)->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->merge($users->filter(fn ($parent) => is_null($parent))->keys())
            ->filter(fn ($id) => $users->has($id))
            ->map($rootOf)
            ->unique();

        $institutionFor = [];
        foreach ($owners as $ownerId) {
            $institutionFor[$ownerId] = DB::table('institutions')->insertGetId([
                'name'       => 'Main Institution',
                'owner_id'   => $ownerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($this->dataTables as $t) {
                DB::table($t)->where('user_id', $ownerId)->update(['institution_id' => $institutionFor[$ownerId]]);
            }
        }

        foreach ($users->keys() as $id) {
            $root = $rootOf($id);
            if (isset($institutionFor[$root])) {
                DB::table('users')->where('id', $id)->update(['institution_id' => $institutionFor[$root]]);
            }
        }
    }

    public function down(): void
    {
        foreach (array_merge($this->dataTables, ['users']) as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('institution_id');
            });
        }

        Schema::dropIfExists('institutions');
    }
};
