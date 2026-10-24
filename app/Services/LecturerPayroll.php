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
 * Optionally only lectures with the given clash statuses count as payable.
 */
class LecturerPayroll
{
    /** Clash statuses, as on the Lecture Administereds page. */
    /** Decimal places kept in hours and money calculations. */
    public const PRECISION = 4;

    public const STATUSES = [
        'ok'                  => 'OK',
        'own_clash'           => 'Own Clash',
        'own_clash_diff_dept' => 'Own Clash - Diff Department',
        'clash_with'          => 'Clash With Other Lecturer',
    ];

    public function __construct(
        protected User $user,
        protected ?string $from = null,
        protected ?string $to = null,
        protected ?array $statuses = null,
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
            ->get();

        $payable = $this->filtering()
            ? $lectures->filter($this->statusMatcher($ownerId, $deptIds, $lecturers->pluck('id')))
            : $lectures;

        $payableByLecturer = $payable->groupBy('lecturer_id');
        $allByLecturer     = $lectures->groupBy('lecturer_id');

        return $lecturers->map(function (Lecturer $lecturer) use ($payableByLecturer, $allByLecturer) {
            $own      = $payableByLecturer->get($lecturer->id, collect());
            $all      = $allByLecturer->get($lecturer->id, collect());
            $exactHours = $own->sum(fn ($l) => self::minutes($l)) / 60;
            $hours      = round($exactHours, self::PRECISION);
            $allHours   = round($all->sum(fn ($l) => self::minutes($l)) / 60, self::PRECISION);

            $rate    = (float) $lecturer->rate_per_hour;
            // Work from the exact hours; keep every amount to 4 decimal places.
            $exactGross = $exactHours * $rate;
            $gross      = round($exactGross, self::PRECISION);
            $tax        = round($exactGross * $lecturer->tax_rate / 100, self::PRECISION);
            $housing    = round($exactGross * $lecturer->housing_levy_rate / 100, self::PRECISION);

            return (object) [
                'id'                => $lecturer->id,
                'name'              => $lecturer->name,
                'department'        => $lecturer->department->name ?? '—',
                'kra_pin'           => $lecturer->kra_pin ?: '—',
                'sessions'          => $own->count(),
                'hours'             => $hours,
                'excluded_sessions' => $all->count() - $own->count(),
                'excluded_hours'    => round($allHours - $hours, self::PRECISION),
                'rate'              => $rate,
                'tax_rate'          => (float) $lecturer->tax_rate,
                'housing_levy_rate' => (float) $lecturer->housing_levy_rate,
                'gross'             => $gross,
                'tax'               => $tax,
                'housing'           => $housing,
                'net'               => round($gross - $tax - $housing, self::PRECISION),
                'last_lecture'      => $own->max('lecture_date'),
                'units'             => $own->groupBy(fn ($l) => $l->unit->name ?? 'No unit')
                    ->map(fn ($g) => round($g->sum(fn ($l) => self::minutes($l)) / 60, self::PRECISION))
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
            'hours'    => round($rows->sum('hours'), self::PRECISION),
            'gross'    => round($rows->sum('gross'), self::PRECISION),
            'tax'      => round($rows->sum('tax'), self::PRECISION),
            'housing'  => round($rows->sum('housing'), self::PRECISION),
            'net'      => round($rows->sum('net'), self::PRECISION),
        ];
    }

    /** Keep only known statuses; null means "all statuses" (no filtering). */
    public static function normaliseStatuses(?array $statuses): ?array
    {
        $valid = array_values(array_intersect(array_keys(self::STATUSES), (array) $statuses));

        return $valid && count($valid) < count(self::STATUSES) ? $valid : null;
    }

    protected function filtering(): bool
    {
        return !empty($this->statuses);
    }

    /**
     * Returns a callback telling whether a lecture has any of the selected statuses.
     * Same rules as the Lecture Administereds page:
     *  - own clash: same lecturer, same date, overlapping time (any department)
     *  - own clash diff dept: as above but the other lecture is in another department
     *  - clash with: same class, date, start and end time, but a different lecturer
     *  - ok: neither own clash nor clash with
     */
    protected function statusMatcher(int $ownerId, ?array $deptIds, Collection $lecturerIds): callable
    {
        $columns = ['id', 'lecturer_id', 'classs_id', 'department_id', 'lecture_date', 'start_time', 'end_time'];

        $sameLecturerDay = LectureAdministered::where('user_id', $ownerId)
            ->whereIn('lecturer_id', $lecturerIds)
            ->get($columns)
            ->groupBy(fn ($l) => $l->lecturer_id . '_' . $l->lecture_date);

        $sameClassSlot = LectureAdministered::where('user_id', $ownerId)
            ->when($deptIds, fn ($q) => $q->whereIn('department_id', $deptIds))
            ->get($columns)
            ->groupBy(fn ($l) => $l->classs_id . '_' . $l->lecture_date . '_' . $l->start_time . '_' . $l->end_time);

        $overlaps = fn ($a, $b) => !($a->end_time <= $b->start_time || $a->start_time >= $b->end_time);
        $selected = $this->statuses;

        return function ($lecture) use ($sameLecturerDay, $sameClassSlot, $overlaps, $selected) {
            $others = $sameLecturerDay->get($lecture->lecturer_id . '_' . $lecture->lecture_date, collect())
                ->filter(fn ($c) => $c->id != $lecture->id && $overlaps($lecture, $c));

            $ownClash  = $others->isNotEmpty();
            $clashWith = $sameClassSlot
                ->get($lecture->classs_id . '_' . $lecture->lecture_date . '_' . $lecture->start_time . '_' . $lecture->end_time, collect())
                ->contains(fn ($c) => $c->id != $lecture->id && $c->lecturer_id != $lecture->lecturer_id);

            $status = [
                'ok'                  => !$ownClash && !$clashWith,
                'own_clash'           => $ownClash,
                'own_clash_diff_dept' => $others->contains(fn ($c) => $c->department_id != $lecture->department_id),
                'clash_with'          => $clashWith,
            ];

            foreach ($selected as $key) {
                if ($status[$key]) {
                    return true;
                }
            }

            return false;
        };
    }

    protected static function minutes(LectureAdministered $lecture): int
    {
        $start = Carbon::parse($lecture->start_time);
        $end   = Carbon::parse($lecture->end_time);

        return max(0, $start->diffInMinutes($end, false));
    }
}
