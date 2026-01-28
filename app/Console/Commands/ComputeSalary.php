<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Salary;
use App\Models\Status;
use App\Models\SalaryPeriod;
use App\Models\TimeLog;
use App\Models\Holiday;
use App\Models\Overtime;
use Carbon\Carbon;

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
        
        // 1. Determine Dates and Cycle
        if ($today->day === 28) {
            $start = $today->copy()->startOfMonth();
            $end = $today->copy()->day(15);
            $cycle = '1st';
        } elseif ($today->day === 1) {
            $start = $today->copy()->subMonth()->day(16);
            $end = $today->copy()->subMonth()->endOfMonth();
            $cycle = '2nd';
        } else {
            $this->warn('Computation only runs on the 1st and 16th.');
            return;
        }

        // 1.1 Get or Create Salary Period
        $period = SalaryPeriod::firstOrCreate([
            'month'      => $start->format('F'),
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'year'       => $start->year,
            'cycle'      => $cycle,
        ]);

        // 2. Determine Workdays (Mon-Sat) in this period and holidays
        $holidaysInRange = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])->get();

        // 3. Determine Workdays (Mon-Sat)
        $workdaysInPeriod = [];
        $tempDate = $start->copy();
        while ($tempDate->lte($end)) {
            if (!$tempDate->isSunday()) {
                $workdaysInPeriod[] = $tempDate->toDateString();
            }
            $tempDate->addDay();
        }
        $totalWorkdaysCount = count($workdaysInPeriod);

        // 4. Process Employees
        User::whereHas('employeeDetails', fn($q) => $q->whereNotNull('current_salary'))
            ->whereHas('status', fn($q) => $q->where('status_name', 'active'))
            ->with(['employeeDetails'])
            ->chunk(100, function ($employees) use ($start, $end, $period, $totalWorkdaysCount, $holidaysInRange) {
                
                $userIds = $employees->pluck('id');
                $approvedStatusId = Status::where('status_name', 'approved')->first()->id;
                $pendingStatusId = Status::where('status_name', 'pending')->first()->id;

                // Bulk fetch approved overtimes
                $allOvertimes = Overtime::whereIn('requester_id', $userIds)
                    ->where('status_id', $approvedStatusId)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->get()
                    ->groupBy('requester_id');

                // Bulk fetch logs
                $allLogs = TimeLog::whereIn('user_id', $userIds)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->whereRaw('DAYOFWEEK(date) != 1') // Exclude Sundays
                    ->get()
                    ->groupBy('user_id');

                foreach ($employees as $employee) {
                    $currentSalary = $employee->employeeDetails->current_salary;
                    $halfSalary = $currentSalary / 2;
                    $dailyRate = $halfSalary / 13;
                    $hourlyRate = $dailyRate / 8; // Assuming 8-hour workday

                    // --- OVERTIME CALCULATION ---
                    $userOvertimes = $allOvertimes->get($employee->id, collect());
                    $totalOTHours = $userOvertimes->sum('total_hours');
                    $otMultiplier = 1.00; // 1.25 for future use + 25%
                    $overtimeAmount = $totalOTHours * ($hourlyRate * $otMultiplier);

                    // --- HOLIDAY CALCULATION ---
                    $holidayAmount = 0;
                    $appliedHolidayIds = [];
                    $userLogs = $allLogs->get($employee->id, collect())->pluck('date')->toArray();
                    $regularHolidaysCount = 0; // to adjust absences

                    foreach ($holidaysInRange as $holiday) {
                        $hasWorked = in_array($holiday->date, $userLogs);

                        if ($holiday->type === 'regular') {
                            $regularHolidaysCount++;
                            // Worked = Double Pay (2.0), Not Worked = Paid (1.0)
                            $multiplier = $hasWorked ? 2.0 : 1.0;
                            $holidayAmount += ($dailyRate * $multiplier);
                            $appliedHolidayIds[] = $holiday->id;
                        } elseif ($holiday->type === 'special' && $hasWorked) {
                            // Special only pays if worked (+30%)
                            $multiplier = 0.30; 
                            $holidayAmount += ($dailyRate * $multiplier);
                            $appliedHolidayIds[] = $holiday->id;
                        }
                    }

                    // --- ABSENCE DEDUCTION ---
                    $daysRequiredToWork = max(0, $totalWorkdaysCount - $regularHolidaysCount); // Don't penalize for not working on a regular holiday 
                    // Filter logs to only count non-holiday work to avoid double counting
                    $actualWorkDays = 0;
                    foreach (array_unique($userLogs) as $logDate) {
                        $isRegularHoliday = $holidaysInRange->where('date', $logDate)->where('type', 'regular')->first();
                        if (!$isRegularHoliday) {
                            $actualWorkDays++;
                        }
                    }
                    $absentDays = max(0, $daysRequiredToWork - $actualWorkDays);
                    $absentDeduction = $absentDays * $dailyRate;
                    
                    // --- FINAL COMPUTATION ---
                    $grossPay = $halfSalary + $overtimeAmount + $holidayAmount;
                    $netPay = max(0, $grossPay - $absentDeduction);

                    // Create record
                    $salary = Salary::create([
                        'user_id'          => $employee->id,
                        'status_id'        => $pendingStatusId,
                        'salary_period_id' => $period->id,
                        'rate_day'         => round($dailyRate, 2),
                        'rate_month'       => $currentSalary,
                        'absent_day'       => $absentDays,
                        'absent_deduction' => round($absentDeduction, 2),
                        'overtime_hour'    => $totalOTHours,
                        'overtime_amount'  => round($overtimeAmount, 2),
                        // 'holiday_amount'   => round($holidayAmount, 2),
                        'gross_pay'        => round($grossPay, 2),
                        'net_pay'          => round($netPay, 2),
                    ]);

                    // Sync Holidays to Pivot
                    if (!empty($appliedHolidayIds)) {
                        $salary->holidays()->sync($appliedHolidayIds);
                    }
                }
            });

        $this->info("Successfully computed salaries for period ID: {$period->id}");
    }
}