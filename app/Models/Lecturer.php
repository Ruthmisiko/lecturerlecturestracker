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
        'rate_per_hour',
        'tax_rate',
        'housing_levy_rate',
    ];

    /** Pay fields only the superuser may set. */
    public const PAY_FIELDS = ['rate_per_hour', 'tax_rate', 'housing_levy_rate'];

    public const DEFAULT_RATE_PER_HOUR     = 1000;
    public const DEFAULT_TAX_RATE          = 30;
    public const DEFAULT_HOUSING_LEVY_RATE = 1.5;

    /** New lecturers get the standard pay settings unless the superuser sets others. */
    protected $attributes = [
        'rate_per_hour'     => self::DEFAULT_RATE_PER_HOUR,
        'tax_rate'          => self::DEFAULT_TAX_RATE,
        'housing_levy_rate' => self::DEFAULT_HOUSING_LEVY_RATE,
    ];

    protected $casts = [
        'rate_per_hour'     => 'float',
        'tax_rate'          => 'float',
        'housing_levy_rate' => 'float',
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
