<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class StaffVisit extends Model
{
    use BelongsToInstitution;

    public const TYPES = [
        'visit'            => 'Visit',
        'exam_supervision' => 'Exam supervision',
        'sports'           => 'Sports / students escort',
        'other'            => 'Other',
    ];

    protected $fillable = [
        'lecturer_id', 'department_id', 'date_of_entry', 'type', 'purpose', 'narration', 'location', 'sponsor', 'itinerary',
        'start_date', 'end_date', 'start_time', 'end_time',
        'days', 'days_manual', 'per_diem_rate', 'fare', 'meals', 'remuneration', 'total_amount', 'status', 'voucher_number', 'paid_on', 'remarks', 'user_id',
    ];

    protected $casts = [
        'start_date'    => 'date:Y-m-d',
        'end_date'      => 'date:Y-m-d',
        'paid_on'       => 'date:Y-m-d',
        'date_of_entry' => 'date:Y-m-d',
        'days_manual'   => 'boolean',
        'per_diem_rate' => 'float',
        'fare'          => 'float',
        'meals'         => 'float',
        'remuneration'  => 'float',
        'total_amount'  => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $visit) {
            // Days come from the dates unless they were typed in by hand.
            if (!$visit->days_manual || !$visit->days) {
                $visit->days = $visit->calculatedDays();
            }
            $visit->total_amount = $visit->computeTotal();
        });
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /** Days worked out from the dates (inclusive). */
    public function calculatedDays(): int
    {
        return max(1, $this->start_date->diffInDays($this->end_date) + 1);
    }

    /** Per diem (rate × days) — the "Rate" column of the claims sheet. */
    public function perDiemAmount(): float
    {
        return round($this->days * $this->per_diem_rate, 2);
    }

    public function computeTotal(): float
    {
        return round($this->perDiemAmount() + $this->fare + $this->meals + $this->remuneration, 2);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function isAllDay(): bool
    {
        return !$this->start_time || !$this->end_time;
    }

    /** e.g. "05 Jan 2026" or "05 Jan 2026 – 07 Jan 2026". */
    public function periodLabel(): string
    {
        $start = $this->start_date->format('d M Y');

        return $this->start_date->equalTo($this->end_date) ? $start : $start . ' – ' . $this->end_date->format('d M Y');
    }

    /**
     * Lectures administered while the lecturer was away: same lecturer, a lecture date inside the visit,
     * and (when the visit has times and is a single day) overlapping hours. Multi-day or all-day visits
     * clash with every lecture in the period.
     */
    public function clashingLectures(): Collection
    {
        $lectures = LectureAdministered::with(['classs', 'unit'])
            ->where('lecturer_id', $this->lecturer_id)
            ->whereBetween('lecture_date', [$this->start_date->format('Y-m-d'), $this->end_date->format('Y-m-d')])
            ->orderBy('lecture_date')->orderBy('start_time')
            ->get();

        return $lectures->filter(fn ($l) => $this->overlapsLecture($l))->values();
    }

    public function overlapsLecture(LectureAdministered $lecture): bool
    {
        if ($this->isAllDay()) {
            return true;
        }

        $date = substr((string) $lecture->lecture_date, 0, 10);
        $from = $date === $this->start_date->format('Y-m-d') ? substr($this->start_time, 0, 5) : '00:00';
        $to   = $date === $this->end_date->format('Y-m-d') ? substr($this->end_time, 0, 5) : '24:00';

        return !(substr($lecture->end_time, 0, 5) <= $from || substr($lecture->start_time, 0, 5) >= $to);
    }
}
