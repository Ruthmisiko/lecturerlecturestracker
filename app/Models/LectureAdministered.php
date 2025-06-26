<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LectureAdministered extends Model
{
    public $table = 'lecture_administereds';

    public $fillable = [
        'lecturer_id',
        'classs_id',
        'lecture_time',
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

}
