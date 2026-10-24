<?php

namespace App\Services;

use App\Models\LectureAdministered;
use App\Models\Lecturer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Computes hours and pay per lecturer from their administered lectures.
 * Gross = hours x rate; tax and housing levy are percentages of gross; net = gross - tax - levy.
 */
class LecturerPayroll
{
    public function __construct(
        protected User $user,
        protected ?string $from = null,
        protected ?string $to = null,
    ) {}

    /** One row per lecturer in scope, sorted by name. */
    public function rows(): Collection
    {
        $ownerId = $this->user->ownerId();
        $deptIds = $this->user->scopedDepartmentIds();

        $lecturers = Lecturer::with('department')
            ->where('user_id', $ownerId)
            ->when($deptIds, fn ($q) => $q->whereIn('department_id', $deptIds))
            ->orderBy('name')
            ->get();

        $lectures = LectureAdministered::with('unit')
            ->where('user_id', $ownerId)
            ->whereIn('lecturer_id', $lecturers->pluck('id'))
            ->when($deptIds, fn ($q) => $q->whereIn('department_id', $deptIds))
            ->when($this->from, fn ($q) => $q->whereDate('lecture_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('lecture_date', '<=', $this->to))
            ->get()
            ->groupBy('lecturer_id');

        return $lecturers->map(function (Lecturer $lecturer) use ($lectures) {
            $own   = $lectures->get($lecturer->id, collect());
            $hours = round($own->sum(fn ($l) => self::minutes($l)) / 60, 2);

            $rate    = (float) $lecturer->rate_per_hour;
            $gross   = round($hours * $rate, 2);
            $tax     = round($gross * $lecturer->tax_rate / 100, 2);
            $housing = round($gross * $lecturer->housing_levy_rate / 100, 2);

            return (object) [
                'id'                => $lecturer->id,
                'name'              => $lecturer->name,
                'department'        => $lecturer->department->name ?? '—',
                'kra_pin'           => $lecturer->kra_pin ?: '—',
                'sessions'          => $own->count(),
                'hours'             => $hours,
                'rate'              => $rate,
                'tax_rate'          => (float) $lecturer->tax_rate,
                'housing_levy_rate' => (float) $lecturer->housing_levy_rate,
                'gross'             => $gross,
                'tax'               => $tax,
                'housing'           => $housing,
                'net'               => round($gross - $tax - $housing, 2),
                'last_lecture'      => $own->max('lecture_date'),
                'units'             => $own->groupBy(fn ($l) => $l->unit->name ?? 'No unit')
                    ->map(fn ($g) => round($g->sum(fn ($l) => self::minutes($l)) / 60, 2))
                    ->sortDesc()
                    ->all(),
            ];
        })->values();
    }

    /** Column totals for a set of rows. */
    public static function totals(Collection $rows): object
    {
        return (object) [
            'sessions' => $rows->sum('sessions'),
            'hours'    => round($rows->sum('hours'), 2),
            'gross'    => round($rows->sum('gross'), 2),
            'tax'      => round($rows->sum('tax'), 2),
            'housing'  => round($rows->sum('housing'), 2),
            'net'      => round($rows->sum('net'), 2),
        ];
    }

    protected static function minutes(LectureAdministered $lecture): int
    {
        $start = Carbon::parse($lecture->start_time);
        $end   = Carbon::parse($lecture->end_time);

        return max(0, $start->diffInMinutes($end, false));
    }
}
