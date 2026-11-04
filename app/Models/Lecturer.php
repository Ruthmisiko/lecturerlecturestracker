<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    use BelongsToInstitution;

    public $table = 'lecturers';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'id_number',
        'kra_pin',
        'account_number',
        'bank_name',
        'bank_branch',
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

    /** Main department; used for grouping on payroll reports. */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /** Every department the lecturer teaches in. */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_lecturer')->withTimestamps();
    }

    /** Lecturers in any of the given departments, as main department or an extra one. */
    public function scopeInDepartments($query, $departmentIds)
    {
        $departmentIds = (array) $departmentIds;

        return $query->where(fn ($q) => $q->whereIn('department_id', $departmentIds)
            ->orWhereHas('departments', fn ($d) => $d->whereIn('departments.id', $departmentIds)));
    }

    /** Saves the department list, keeping the current main department when it is still in the list. */
    public function setDepartments(array $departmentIds): void
    {
        $departmentIds = array_values(array_unique(array_filter(array_map('intval', $departmentIds))));
        $main = in_array((int) $this->department_id, $departmentIds, true) ? $this->department_id : ($departmentIds[0] ?? null);

        $this->forceFill(['department_id' => $main])->save();
        $this->departments()->sync($departmentIds);
    }

    /** Department names joined for display, e.g. "HRI, Clinical Medicine". */
    public function departmentNames(): string
    {
        $names = $this->departments->pluck('name');
        if ($names->isEmpty() && $this->department) {
            $names = collect([$this->department->name]);
        }

        return $names->join(', ') ?: '-';
    }

    public function units()
    {
        return $this->belongsToMany(Unit::class, 'lecturer_unit');
    }
    
}
