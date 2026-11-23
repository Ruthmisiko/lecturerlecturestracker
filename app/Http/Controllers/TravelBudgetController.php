<?php

namespace App\Http\Controllers;

use App\Services\TravelBudgets;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Shows how much of each department's travel budget has been spent on staff visits. */
class TravelBudgetController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(Auth::user()?->hasAccess('staff-visit.manage'), 403);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        [$fyFrom, $fyTo] = TravelBudgets::financialYear();
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : $fyFrom;
        $to   = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : $fyTo;

        $rows = app(TravelBudgets::class)->summary($user->ownerId(), $from, $to, $user->scopedDepartmentIds());

        // Financial years to pick from: the current one and the previous three.
        $years = collect(range(0, 3))->map(fn ($i) => TravelBudgets::financialYear(now()->subYears($i)))
            ->map(fn ($y) => ['from' => $y[0]->toDateString(), 'to' => $y[1]->toDateString(), 'label' => TravelBudgets::financialYearLabel($y[0])]);

        return view('travel_budgets.index', [
            'rows'   => $rows,
            'from'   => $from,
            'to'     => $to,
            'years'  => $years,
            'totals' => (object) [
                'allocated' => $rows->sum('allocated'),
                'spent'     => $rows->sum('spent'),
                'balance'   => $rows->whereNotNull('allocated')->sum('balance'),
                'exhausted' => $rows->where('state', 'exhausted')->count(),
            ],
        ]);
    }
}
