<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryPeriod;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Services\AttendanceCalculationService;

class BiMonthlyController extends Controller
{
    public function index(Request $request)
    {
        // Fetch Salary Period
        $biMonthlyReports = SalaryPeriod::orderBy('start_date', 'desc')
            ->get()
            ->toArray();

        $periodInfo = $this->getPeriodInfo();
        $period = SalaryPeriod::where('start_date', $periodInfo['start']->toDateString())
            ->where('end_date', $periodInfo['end']->toDateString())
            ->first();

        if (!$period) {
            abort(404);
        }

        // Initialize the base props
        $props = [
            'biMonthlyReports' => $biMonthlyReports,
            'currentPeriod' => $period,
            'periodDates' => [
                    'label' => "{$periodInfo['start']->format('M d')} - {$periodInfo['end']->format('M d, Y')}",
                    'start' => $periodInfo['start']->toDateString(),
                    'end' => $periodInfo['end']->toDateString(),
                ]
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

    public function recompute(Request $request, AttendanceCalculationService $service)
    {
        $request->validate(['salary_period_id' => 'required']);
        $periodInfo = $this->getPeriodInfo();
        $currentPeriod = SalaryPeriod::where('start_date', $periodInfo['start']->toDateString())
            ->where('end_date', $periodInfo['end']->toDateString())
            ->first();

        // Checker
        if (!$currentPeriod || $request->salary_period_id !== $currentPeriod->id) {
            return back()->withErrors(['message' => 'You can only recompute the current active period.']);
        }

        $period = SalaryPeriod::findOrFail($request->salary_period_id);

        // Get all active users
        $users = User::whereHas('status', fn ($q) => $q->where('status_name', 'active'))
            ->whereHas('employeeDetails')
            ->get();

        if ($users->isEmpty()) {
            abort(403, 'no users found matching the criteria');
        } else {
            $service->compute($users, $period);
        }

        return back()->with('success', 'Attendance reports re-computed!');
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
                'id'    => $period->id,
                'label' => $periodLabel,
                'days'  => $dayCount,
            ],
            'employees' => $employees,
        ]);
    }
}
