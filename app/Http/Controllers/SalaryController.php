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
            ->where('status_id', 10) // Active
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
