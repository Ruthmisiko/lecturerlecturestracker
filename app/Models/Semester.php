<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use BelongsToInstitution;

    protected $fillable = ['name', 'start_date', 'end_date', 'is_current', 'user_id'];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
        'is_current' => 'boolean',
    ];

    public function lectureAdministereds()
    {
        return $this->hasMany(LectureAdministered::class);
    }

    /** The current semester of the institution being worked in. */
    public static function current(): ?self
    {
        return static::where('is_current', true)->latest('id')->first();
    }

    /** Make this the institution's only current semester. */
    public function makeCurrent(): void
    {
        static::where('id', '!=', $this->id)->where('is_current', true)->update(['is_current' => false]);
        $this->update(['is_current' => true]);
    }

    /** e.g. "05 Jan 2026 – 30 Apr 2026", or "" when no dates are set. */
    public function periodLabel(): string
    {
        if (!$this->start_date && !$this->end_date) {
            return '';
        }

        return ($this->start_date?->format('d M Y') ?? '…') . ' – ' . ($this->end_date?->format('d M Y') ?? '…');
    }
}
