<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    public $table = 'lecturers';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'id_number',
        'kra_pin',
        'specialization',
        'department_id',
    ];


    protected $casts = [
        
    ];

    public static array $rules = [
        
    ];
    public function lectureAdministereds()
    {
        return $this->hasMany(\App\Models\LectureAdministered::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function units()
    {
        return $this->belongsToMany(Unit::class, 'lecturer_unit');
    }
    
}
