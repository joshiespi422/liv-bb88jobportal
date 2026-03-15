<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryPeriod;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;

class BiMonthlyController extends Controller
{
    public function index(Request $request)
    {
        // Fetch Salary Period
        $biMonthlyReports = SalaryPeriod::orderBy('start_date', 'desc')
            ->get()
            ->toArray();

        // Initialize the base props
        $props = [
            'biMonthlyReports' => $biMonthlyReports,
        ];

        return Inertia::render('BiMonthlyView', $props);
    }

    private function getPeriodInfo()
    {
        $today = Carbon::today();
        if ($today->day >= 16) {
            return ['start' => $today->copy()->startOfMonth(), 'end' => $today->copy()->day(15)];
        }
        return ['start' => $today->copy()->subMonth()->day(16), 'end' => $today->copy()->subMonth()->endOfMonth()];
    }

    public function show(SalaryPeriod $period)
    {
        $start = Carbon::parse($period->start_date);
        $end   = Carbon::parse($period->end_date);

        $dayCount = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (! $cursor->isSunday()) $dayCount++;
            $cursor->addDay();
        }

        $periodLabel = $start->format('F') . ' '
            . $start->format('j') . '–'
            . $end->format('j') . ', '
            . $period->year;

        $employees = User::select('id', 'name', 'position')
            ->with([
                'attendanceReports' => fn ($q) => $q->where('salary_period_id', $period->id),
                'attendanceReports.holidays',
            ])
            ->whereHas('attendanceReports', fn ($q) => $q->where('salary_period_id', $period->id))
            ->get()
            ->map(function ($user) {
                $report = $user->attendanceReports->first();

                $regularCount = $report->holidays->where('type', 'regular')->count();
                $specialCount = $report->holidays->where('type', 'special')->count();

                $holidayParts = array_filter([
                    $regularCount ? "{$regularCount}R" : null,
                    $specialCount ? "{$specialCount}S" : null,
                ]);

                return [
                    'name'     => $user->name,
                    'position' => $user->position,
                    'absent'   => $report->absent,
                    'halfday'  => $report->halfday,
                    'holiday'  => implode(', ', $holidayParts) ?: '-',
                    'lates'    => $report->lates,
                    'overtime' => $report->overtime,
                    'total'    => $report->total,
                ];
            });

        return response()->json([
            'period' => [
                'label' => $periodLabel,
                'days'  => $dayCount,
            ],
            'employees' => $employees,
        ]);
    }
}
