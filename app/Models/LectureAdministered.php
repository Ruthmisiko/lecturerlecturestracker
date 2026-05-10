<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LectureAdministered extends Model
{
    public $table = 'lecture_administereds';

    public $fillable = [
        'user_id',
        'lecturer_id',
        'classs_id',
        'department_id',
        'unit_id',
        'start_time',
        'end_time',
        'lecture_date',
    ];

    protected $casts = [
        
    ];

    public static array $rules = [
        
    ];

   

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function classs()
    {
        return $this->belongsTo(Classs::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
