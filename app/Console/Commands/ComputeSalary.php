<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Salary;
use App\Models\Status;
use App\Models\SalaryPeriod;
use App\Models\TimeLog;
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
        if ($today->day === 16) {
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

        // 2. Get or Create Salary Period
        $period = SalaryPeriod::firstOrCreate([
            'month'      => $start->format('F'),
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'year'       => $start->year,
            'cycle'      => $cycle,
        ]);

        // 3. Determine Workdays (Mon-Sat) in this period
        $workdaysInPeriod = [];
        $tempDate = $start->copy();
        while ($tempDate->lte($end)) {
            if (!$tempDate->isSunday()) {
                $workdaysInPeriod[] = $tempDate->toDateString();
            }
            $tempDate->addDay();
        }
        $totalWorkdaysCount = count($workdaysInPeriod);

        // 4. Process Employees in Chunks (Efficient for large data)
        User::whereHas('employeeDetails', function ($query) {
                $query->whereNotNull('current_salary'); // Skip if salary is null
            })
            ->whereHas('status', function ($query) {
                $query->where('status_name', 'active'); // Skip if not active
            })
            ->with('employeeDetails')
            ->chunk(100, function ($employees) use ($start, $end, $period, $totalWorkdaysCount) {
                
                $bulkData = [];
                $userIds = $employees->pluck('id');
                $pendingStatusId = Status::where('status_name', 'pending')->first()->id;

                // Bulk fetch logs for all employees in this chunk to avoid N+1 queries
                $allLogs = TimeLog::whereIn('user_id', $userIds)
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->whereRaw('DAYOFWEEK(date) != 1') // Exclude Sundays
                    ->get()
                    ->groupBy('user_id');

                foreach ($employees as $employee) {
                    $currentSalary = $employee->employeeDetails->current_salary;
                    $halfSalary = $currentSalary / 2;
                    
                    // Get unique dates worked for this employee
                    $daysWorked = isset($allLogs[$employee->id]) 
                        ? $allLogs[$employee->id]->pluck('date')->unique()->count() 
                        : 0;

                    $absentDays = max(0, $totalWorkdaysCount - $daysWorked);
                    
                    // Logic: Daily Rate = Half Salary / 13
                    $overtimeAmount = 0; // Placeholder for future implementation
                    $grossPay = $halfSalary + $overtimeAmount;
                    $netPay = max(0, $grossPay - $absentDeduction);
                    $dailyRate = $halfSalary / 13;
                    $absentDeduction = $absentDays * $dailyRate;

                    $bulkData[] = [
                        'user_id'           => $employee->id,
                        'status_id'         => $pendingStatusId,
                        'salary_period_id'  => $period->id,
                        'rate_day'          => round($dailyRate, 2),
                        'rate_month'        => $currentSalary,
                        'absent_day'        => $absentDays,
                        'absent_deduction'  => round($absentDeduction, 2),
                        'overtime_hour'     => 0, // Placeholder
                        'overtime_amount'   => 0, // Placeholder
                        'gross_pay'         => round($grossPay, 2),
                        'net_pay'           => round($netPay, 2),
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                }

                // 5. Bulk Insert for this chunk
                if (!empty($bulkData)) {
                    Salary::insert($bulkData);
                }
            });

        $this->info("Successfully computed salaries for period ID: {$period->id}");
    }
}