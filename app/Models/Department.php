<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use BelongsToInstitution;

    public $table = 'departments';

    protected $fillable = [
        'user_id',
        'name',
        'code',
        'description',
        'approved_hours',
        'travel_budget',
        'travel_budget_parent_id',
    ];

    protected $casts = [
        'approved_hours' => 'float',
        'travel_budget'  => 'float',
    ];

    public static array $rules = [];

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function lecturers()
    {
        return $this->hasMany(Lecturer::class);
    }

    /** The department whose travel budget this one draws on (e.g. Accounts → Administration), if any. */
    public function travelBudgetParent()
    {
        return $this->belongsTo(self::class, 'travel_budget_parent_id');
    }

    /** The department that holds the travel budget this one is charged to (itself unless it draws on another). */
    public function travelBudgetHolderId(): int
    {
        return (int) ($this->travel_budget_parent_id ?: $this->id);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
