<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class LectureAdministered extends Model
{
    use BelongsToInstitution;

    public $table = 'lecture_administereds';

    public $fillable = [
        'user_id',
        'lecturer_id',
        'classs_id',
        'department_id',
        'unit_id',
        'semester_id',
        'start_time',
        'end_time',
        'lecture_date',
    ];

    protected $casts = [
        
    ];

    public static array $rules = [

    ];

    protected static function booted(): void
    {
        // New lectures (form, import...) default to the institution's current semester.
        static::creating(function (self $lecture) {
            if (empty($lecture->semester_id)) {
                $lecture->semester_id = Semester::current()?->id;
            }
        });
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

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
