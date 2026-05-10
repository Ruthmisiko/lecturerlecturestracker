<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    public $table = 'departments';

    protected $fillable = [
        'user_id',
        'name',
        'code',
        'description',
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
