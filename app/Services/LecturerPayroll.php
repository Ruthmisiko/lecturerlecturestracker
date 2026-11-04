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
            ->when($deptIds, fn ($q) => $q->inDepartments($deptIds))
            ->orderBy('name')
            ->get();

        $lectures = LectureAdministered::with('unit', 'department')
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
            $own = $payableByLecturer->get($lecturer->id, collect());
            $all = $allByLecturer->get($lecturer->id, collect());

            // Share of each unit's hours that is paid (below 1 when the unit is over its max hours), so a
            // capped unit is cut back by the same proportion in every department it was taught in.
            $unitFactor = $own->groupBy(fn ($l) => $l->unit_id ?? 0)->map(function ($group) {
                $actual = $group->sum(fn ($l) => self::minutes($l)) / 60;
                $max    = $group->first()->unit?->max_hours;

                return $max !== null && $actual > (float) $max ? (float) $max / $actual : 1.0;
            });

            // Lectures count under the department they were recorded in (the lecturer's main one when not set).
            $deptKey     = fn ($l) => (int) ($l->department_id ?: $lecturer->department_id);
            $departments = $all->groupBy($deptKey)->map(function ($group, $key) use ($lecturer, $own, $deptKey, $unitFactor) {
                $dept = $group->first(fn ($l) => $l->department)?->department
                    ?? ($key == $lecturer->department_id ? $lecturer->department : null);

                return (object) ([
                    'department_key'         => (int) $key,
                    'department'             => $dept->name ?? '—',
                    'department_id'          => $dept?->id,
                    'department_description' => trim($dept->description ?? '') ?: ($dept->name ?? ''),
                ] + $this->figures($lecturer, $own->filter(fn ($l) => $deptKey($l) == $key), $group, $unitFactor));
            })->sortBy('department')->values()->all();

            return (object) ([
                'id'                => $lecturer->id,
                'name'              => $lecturer->name,
                'department'        => $lecturer->department->name ?? '—',
                'department_id'     => $lecturer->department_id,
                'department_description' => trim($lecturer->department->description ?? '') ?: ($lecturer->department->name ?? ''),
                'kra_pin'           => $lecturer->kra_pin ?: '—',
                'email'             => $lecturer->email,
                'phone'             => $lecturer->phone,
                'id_number'         => $lecturer->id_number,
                'account_number'    => $lecturer->account_number,
                'bank_name'         => $lecturer->bank_name,
                'bank_branch'       => $lecturer->bank_branch,
                'rate'              => (float) $lecturer->rate_per_hour,
                'tax_rate'          => (float) $lecturer->tax_rate,
                'housing_levy_rate' => (float) $lecturer->housing_levy_rate,
            ] + $this->figures($lecturer, $own, $all, $unitFactor) + [
                // The same figures split by the department each lecture was recorded in (one payslip each).
                'departments'       => $departments,
            ]);
        })->values();
    }

    /** Hours and pay for a set of a lecturer's lectures ($own = payable ones, $all = every one in the period). */
    protected function figures(Lecturer $lecturer, Collection $own, Collection $all, Collection $unitFactor): array
    {
        // Hours per unit, capped at the unit's max hours when one is set (units with no max are unchanged).
        $unitDetails = $own->groupBy(fn ($l) => $l->unit_id ?? 0)->map(function ($group, $unitId) use ($unitFactor) {
            $unit    = $group->first()->unit;
            $actual  = $group->sum(fn ($l) => self::minutes($l)) / 60;
            $factor  = $unitFactor->get($unitId, 1.0);
            $counted = $actual * $factor;

            return [
                'name'    => $unit->name ?? 'No unit',
                'code'    => $unit->unitCode ?? null,
                'actual'  => round($actual, self::PRECISION),
                'counted' => round($counted, self::PRECISION),
                'max'     => $unit?->max_hours !== null ? (float) $unit->max_hours : null,
                'capped'  => $factor < 1,
                '_exact'  => $counted,
            ];
        })->sortByDesc('counted')->values();

        $statusHours = $own->sum(fn ($l) => self::minutes($l)) / 60;   // before the unit caps
        $exactHours  = $unitDetails->sum('_exact');
        $hours       = round($exactHours, self::PRECISION);
        $allHours    = round($all->sum(fn ($l) => self::minutes($l)) / 60, self::PRECISION);
        $unitDetails = $unitDetails->map(fn ($d) => array_diff_key($d, ['_exact' => 1]))->all();

        // Work from the exact hours; keep every amount to 4 decimal places.
        $exactGross = $exactHours * (float) $lecturer->rate_per_hour;
        $gross      = round($exactGross, self::PRECISION);
        $tax        = round($exactGross * $lecturer->tax_rate / 100, self::PRECISION);
        $housing    = round($exactGross * $lecturer->housing_levy_rate / 100, self::PRECISION);

        return [
            'sessions'          => $own->count(),
            'hours'             => $hours,
            'excluded_sessions' => $all->count() - $own->count(),
            'excluded_hours'    => round($allHours - round($statusHours, self::PRECISION), self::PRECISION),
            // Hours above a unit's max that are not paid (0 when no unit is over its max).
            'capped_hours'      => round($statusHours - $exactHours, self::PRECISION),
            'gross'             => $gross,
            'tax'               => $tax,
            'housing'           => $housing,
            // Employer matches the employee's Affordable Housing Levy at the same rate.
            'employer_levy'     => $housing,
            'net'               => round($gross - $tax - $housing, self::PRECISION),
            'last_lecture'      => $own->max('lecture_date'),
            'total_sessions'    => $all->count(),
            'period_taught'     => self::periodTaught($own->min('lecture_date'), $own->max('lecture_date')),
            // name => paid hours (kept for existing displays); unit_details has actual/max/capped.
            'units'             => collect($unitDetails)->mapWithKeys(fn ($d) => [$d['name'] => $d['counted']])->all(),
            'unit_details'      => $unitDetails,
        ];
    }

    /**
     * One payslip per department the lecturer taught in during the period (just the row itself when
     * they taught in no department or one). Each slip is the row with that department's figures.
     */
    public static function slips(object $row, ?int $departmentKey = null): array
    {
        $parts = collect($row->departments ?? [])
            ->when($departmentKey !== null, fn ($c) => $c->where('department_key', $departmentKey));

        if ($parts->isEmpty()) {
            return $departmentKey === null ? [$row] : [];
        }

        return $parts->map(fn ($d) => (object) (array_merge((array) $row, (array) $d, ['departments' => []])))->values()->all();
    }

    /** e.g. "SEP 24-FEB 25", or "MAR 26" when all lectures fall in one month. */
    public static function periodTaught(?string $first, ?string $last): string
    {
        if (!$first) {
            return '';
        }

        $from = strtoupper(Carbon::parse($first)->format('M y'));
        $to   = strtoupper(Carbon::parse($last)->format('M y'));

        return $from === $to ? $from : "{$from}-{$to}";
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
            'employer_levy' => round($rows->sum('employer_levy'), self::PRECISION),
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
