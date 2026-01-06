<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Salary;
use App\Models\TimeLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ComputeSalary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:compute-salary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compute bi-monthly employee payslips';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $startDate = null;
        $endDate = null;

        // 1. Determine the date range based on today's date
        if ($today->day === 16) {
            // Computing for 1st to 15th of current month
            $startDate = $today->copy()->startOfMonth();
            $endDate = $today->copy()->day(15);
        } elseif ($today->day === 1) {
            // Computing for 16th to end of previous month
            $startDate = $today->copy()->subMonth()->day(16);
            $endDate = $today->copy()->subMonth()->endOfMonth();
        } else {
            $this->info('Today is not a computation day.');
            return;
        }

        // 2. Get active employees with details
        $employees = User::whereHas('employeeDetails')
            ->whereHas('status', fn($q) => $q->where('status_name', 'active'))
            ->with('employeeDetails')
            ->get();

        foreach ($employees as $employee) {
            $this->computeForEmployee($employee, $startDate, $endDate);
        }

        $this->info("Salary computation completed for {$startDate->toDateString()} to {$endDate->toDateString()}");
    }

    private function computeForEmployee($user, $start, $end)
    {
        $currentSalary = $user->employeeDetails->current_salary;
        $halfSalary = $currentSalary / 2;
        
        // 3. Identify Workdays (Mon-Sat) in the period
        $workDaysInPeriod = [];
        $tempDate = $start->copy();
        while ($tempDate->lte($end)) {
            if (!$tempDate->isSunday()) {
                $workDaysInPeriod[] = $tempDate->toDateString();
            }
            $tempDate->addDay();
        }

        $totalWorkDaysCount = count($workDaysInPeriod);
        
        // 4. Get Time Logs for the period (excluding Sundays)
        $logs = TimeLog::where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereRaw('DAYOFWEEK(date) != 1') // 1 is Sunday in MySQL
            ->pluck('date')
            ->unique()
            ->toArray();

        $daysWorkedCount = count($logs);
        $absentDaysCount = $totalWorkDaysCount - $daysWorkedCount;

        // 5. Logic: Compute Deductions
        // Per your requirement: Daily rate = (Half Salary) / 13
        $dailyRate = $halfSalary / 13; 
        $absentDeduction = $absentDaysCount * $dailyRate;
        $grossPay = $halfSalary - $absentDeduction;

        // 6. Save to Salaries table
        Salary::create([
            'user_id' => $user->id,
            'rate_day' => $dailyRate,
            'rate_month' => $currentSalary,
            'absent_day' => $absentDaysCount,
            'absent_deduction' => $absentDeduction,
            'gross_pay' => max($grossPay, 0), // Ensure it doesn't go negative
            'created_at' => now(),
        ]);
    }
}
