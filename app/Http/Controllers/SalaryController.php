<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryPeriod;
use App\Models\User;
use App\Models\Salary;
use App\Models\TimeLog;
use App\Models\Status;
use Carbon\Carbon;
use Inertia\Inertia;

class SalaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $periodInfo = $this->getPeriodInfo();
        
        $period = SalaryPeriod::where('start_date', $periodInfo['start'])
            ->where('end_date', $periodInfo['end'])
            ->first();

        $employees = User::query()
            ->whereHas('employeeDetails')
            ->whereHas('status', fn($q) => $q->where('status_name', 'active'))
            ->with(['employeeDetails', 'salaries' => function($q) use ($period) {
                $q->where('salary_period_id', $period?->id);
            }])
            ->select(['id', 'name', 'qr_code', 'position'])
            ->get();

        return Inertia::render('SalaryView', [
            'employees' => $employees,
            'currentPeriod' => $period,
            'periodDates' => [
                'label' => "{$periodInfo['start']->format('M d')} - {$periodInfo['end']->format('M d, Y')}",
                'start' => $periodInfo['start']->toDateString(),
                'end' => $periodInfo['end']->toDateString(),
            ]
        ]);
    }

    private function getPeriodInfo()
    {
        $today = Carbon::today();
        if ($today->day >= 16) {
            return ['start' => $today->copy()->startOfMonth(), 'end' => $today->copy()->day(15)];
        }
        return ['start' => $today->copy()->subMonth()->day(16), 'end' => $today->copy()->subMonth()->endOfMonth()];
    }

    public function recomputeAll(Request $request)
    {
        $request->validate(['salary_period_id' => 'required']);
        $period = SalaryPeriod::findOrFail($request->salary_period_id);

        // Get all active users who have a PENDING salary in this period
        $users = User::whereHas('salaries', function($q) use ($period) {
            $q->where('salary_period_id', $period->id)->whereHas('status', fn($s) => $s->where('status_name', 'pending'));
        })
        ->whereHas('employeeDetails', function ($q) {
            $q->whereNotNull('current_salary');
        })
        ->with('employeeDetails')->get();

        if ($users->isEmpty()) {
            abort(403, 'No user with salary set');
        }

        $this->computeLogic($users, $period);

        return back()->with('success', 'All pending salaries re-computed!');
    }

    private function computeLogic($users, $period)
    {
        $workdays = [];
        $tempDate = Carbon::parse($period->start_date);
        $endDate = Carbon::parse($period->end_date);
        while ($tempDate->lte($endDate)) {
            if (!$tempDate->isSunday()) $workdays[] = $tempDate->toDateString();
            $tempDate->addDay();
        }
        $totalWorkdays = count($workdays);
        $pendingId = Status::where('status_name', 'pending')->first()->id;

        foreach ($users as $user) {
            $logs = TimeLog::where('user_id', $user->id)
                ->whereBetween('date', [$period->start_date, $period->end_date])
                ->whereRaw('DAYOFWEEK(date) != 1')
                ->pluck('date')->unique()->count();

            $absentDays = max(0, $totalWorkdays - $logs);
            $halfSalary = $user->employeeDetails->current_salary / 2;
            $dailyRate = $halfSalary / 13;
            $deduction = $absentDays * $dailyRate;
            
            $gross = $halfSalary; // Earnings
            $net = max(0, $gross - $deduction); // Final pay

            Salary::updateOrCreate(
                ['user_id' => $user->id, 'salary_period_id' => $period->id],
                [
                    'status_id' => $pendingId,
                    'rate_day' => $dailyRate,
                    'rate_month' => $user->employeeDetails->current_salary,
                    'absent_day' => $absentDays,
                    'absent_deduction' => $deduction,
                    'gross_pay' => $gross,
                    'net_pay' => $net,
                ]
            );
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
