<?php

namespace App\Services;

use App\Models\Department;
use App\Models\StaffVisit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Monitors staff-travel spending against each department's travel budget.
 * A department that "draws on" another (Accounts → Administration) is charged to that budget holder.
 * The budget is the yearly allocation; spending is counted by visit start date within the chosen period.
 */
class TravelBudgets
{
    /** The financial year (1 Jul – 30 Jun) containing the date, as [from, to]. */
    public static function financialYear(?Carbon $date = null): array
    {
        $date  ??= now();
        $start = $date->month >= 7 ? $date->year : $date->year - 1;

        return [Carbon::create($start, 7, 1)->startOfDay(), Carbon::create($start + 1, 6, 30)->endOfDay()];
    }

    public static function financialYearLabel(Carbon $from): string
    {
        return 'FY ' . $from->year . '/' . ($from->year + 1);
    }

    /**
     * One row per budget holder (a department not drawing on another), most-spent first.
     *
     * @return Collection<int, object>
     */
    public function summary(int $ownerId, Carbon $from, Carbon $to, ?array $onlyDepartmentIds = null): Collection
    {
        $departments = Department::where('user_id', $ownerId)->orderBy('name')->get();
        $holderOf    = $departments->mapWithKeys(fn ($d) => [$d->id => $d->travelBudgetHolderId()]);

        $visits = StaffVisit::where('user_id', $ownerId)
            ->whereDate('start_date', '>=', $from->toDateString())
            ->whereDate('start_date', '<=', $to->toDateString())
            ->get(['department_id', 'total_amount', 'status', 'days']);

        $spent = $visits->groupBy(fn ($v) => $holderOf[$v->department_id] ?? 0);

        $rows = $departments->filter(fn ($d) => !$d->travel_budget_parent_id)->map(function ($d) use ($departments, $spent) {
            $mine  = $spent->get($d->id, collect());
            $total = round($mine->sum('total_amount'), 2);
            $alloc = $d->travel_budget;

            return (object) [
                'id'        => $d->id,
                'name'      => $d->name,
                'members'   => $departments->where('travel_budget_parent_id', $d->id)->pluck('name')->all(),
                'allocated' => $alloc,
                'spent'     => $total,
                'paid'      => round($mine->where('status', 'paid')->sum('total_amount'), 2),
                'pending'   => round($mine->where('status', '!=', 'paid')->sum('total_amount'), 2),
                'visits'    => $mine->count(),
                'balance'   => $alloc === null ? null : round($alloc - $total, 2),
                'percent'   => $alloc ? round($total / $alloc * 100, 1) : null,
                'state'     => $alloc === null ? 'none' : ($total >= $alloc ? 'exhausted' : ($total >= 0.9 * $alloc ? 'near' : 'ok')),
            ];
        });

        if ($onlyDepartmentIds) {
            $rows = $rows->filter(fn ($r) => in_array($r->id, $onlyDepartmentIds) || collect($onlyDepartmentIds)->contains(fn ($id) => ($holderOf[$id] ?? null) === $r->id));
        }

        // Visits with no department can't be charged to a budget; show them so nothing is hidden.
        if (!$onlyDepartmentIds && ($orphans = $spent->get(0)) && $orphans->isNotEmpty()) {
            $rows->push((object) [
                'id' => 0, 'name' => 'No department', 'members' => [], 'allocated' => null,
                'spent' => round($orphans->sum('total_amount'), 2),
                'paid' => round($orphans->where('status', 'paid')->sum('total_amount'), 2),
                'pending' => round($orphans->where('status', '!=', 'paid')->sum('total_amount'), 2),
                'visits' => $orphans->count(), 'balance' => null, 'percent' => null, 'state' => 'none',
            ]);
        }

        return $rows->sortByDesc('spent')->values();
    }

    /** Remaining budget (null = no budget set) for the holder of a department in the visit's financial year. */
    public function remainingFor(int $ownerId, ?int $departmentId, Carbon $date): ?float
    {
        if (!$departmentId || !($department = Department::where('user_id', $ownerId)->find($departmentId))) {
            return null;
        }

        [$from, $to] = self::financialYear($date);
        $row = $this->summary($ownerId, $from, $to)->firstWhere('id', $department->travelBudgetHolderId());

        return $row?->balance;
    }
}
