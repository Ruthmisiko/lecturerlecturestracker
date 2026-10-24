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
    ];

    protected $casts = [
        'approved_hours' => 'float',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
