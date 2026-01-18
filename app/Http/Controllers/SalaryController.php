<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryPeriod;
use App\Models\User;
use App\Models\Salary;
use App\Models\TimeLog;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

        if (!$period) {
            abort(404);
        }

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

    public function recompute(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id', 'salary_period_id' => 'required']);
        
        $period = SalaryPeriod::findOrFail($request->salary_period_id);
        $user = User::where('id', $request->user_id)
            ->with('employeeDetails')
            ->withCount(['timeLogs as attended_days_count' => function ($query) use ($period) {
                $query->whereBetween('date', [$period->start_date, $period->end_date])
                    ->whereRaw('DAYOFWEEK(date) != 1') // Exclude Sundays
                    ->select(DB::raw('count(distinct date)'));
            }])
            ->firstOrFail();
        
        $this->computeLogic([$user], $period);

        return back()->with('success', 'Salary re-computed successfully!');
    }

    public function recomputeAll(Request $request)
    {
        $request->validate(['salary_period_id' => 'required']);
        $period = SalaryPeriod::findOrFail($request->salary_period_id);

        // Get all active users who have a PENDING salary in this period
        $users = User::where(function ($query) use ($period) {
                $query->whereHas('salaries', function ($q) use ($period) {
                    $q->where('salary_period_id', $period->id)
                    ->whereHas('status', fn($s) => $s->where('status_name', 'pending'));
                })
                ->orWhereDoesntHave('salaries', function ($q) use ($period) {
                    $q->where('salary_period_id', $period->id);
                });
            })
            ->whereHas('employeeDetails', function ($q) {
                $q->whereNotNull('current_salary');
            })
            ->with('employeeDetails') 
            // Efficiently count logs for the period excluding Sundays
            ->withCount(['timeLogs as attended_days_count' => function ($query) use ($period) {
                $query->whereBetween('date', [$period->start_date, $period->end_date])
                    ->whereRaw('DAYOFWEEK(date) != 1') // Exclude Sundays
                    ->select(DB::raw('count(distinct date)')); // Ensure unique dates
            }])
            ->get();

        if ($users->isEmpty()) {
            abort(403, 'No users found matching the criteria');
        }

        $this->computeLogic($users, $period);

        return back()->with('success', 'All pending salaries re-computed!');
    }

    private function computeLogic($users, $period)
    {
        // 1. Pre-calculate period constants
        $startDate = Carbon::parse($period->start_date);
        $endDate = Carbon::parse($period->end_date);

        // Calculate total workdays (excluding Sundays) using Carbon's built-in diff
        $totalWorkdays = $startDate->diffInDaysFiltered(function (Carbon $date) {
            return !$date->isSunday();
        }, $endDate->addDay(1));

        // 2. Cache the Status ID once
        $pendingStatusId = Status::where('status_name', 'pending')->value('id');

        // 3. Process users
        foreach ($users as $user) {
            // Data is already available from withCount
            $logsCount = $user->attended_days_count; 
            $absentDays = max(0, $totalWorkdays - $logsCount);

            $currentSalary = $user->employeeDetails->current_salary;
            $halfSalary = $currentSalary / 2;

            // Logic: Half salary divided by 13 (Standard practice for bi-monthly)
            $dailyRate = $halfSalary / 13;
            $deduction = $absentDays * $dailyRate;
            
            $netPay = max(0, $halfSalary - $deduction);

            Salary::updateOrCreate(
                [
                    'user_id' => $user->id, 
                    'salary_period_id' => $period->id
                ],
                [
                    'status_id' => $pendingStatusId,
                    'rate_day' => $dailyRate,
                    'rate_month' => $currentSalary,
                    'absent_day' => $absentDays,
                    'absent_deduction' => $deduction,
                    'gross_pay' => $halfSalary,
                    'net_pay' => $netPay,
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
