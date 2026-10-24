<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use BelongsToInstitution;

    public $table = 'units';

    public $fillable = [
        'user_id',
        'name',
        'unitCode',
        'department_id',
        'max_hours',
    ];

    protected $casts = [
        'max_hours' => 'float',
    ];

    public static array $rules = [

    ];

    public function classses()
    {
        return $this->belongsToMany(Classs::class, 'class_unit', 'unit_id', 'classs_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function lecturers()
    {
        return $this->belongsToMany(Lecturer::class, 'lecturer_unit');
    }

}
