<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    protected $fillable = ['name', 'owner_id'];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function lecturers()
    {
        return $this->hasMany(Lecturer::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
