<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\SalaryPeriod;
use Carbon\Carbon;
use App\Services\SalaryCalculationService;

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
    public function handle(SalaryCalculationService $service)
    {
        $today = Carbon::today();
        
        // Determine Dates and Cycle
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

        $period = SalaryPeriod::firstOrCreate([
            'month' => $start->format('F'),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'year' => $start->year,
            'cycle' => $cycle,
        ]);

        $users = User::whereHas('employeeDetails', fn($q) => $q->whereNotNull('current_salary'))
            ->whereHas('status', fn($q) => $q->where('status_name', 'active'))
            ->get();

        $service->compute($users, $period);

        $this->info("Salaries computed for {$period->month} {$period->cycle} cycle.");
    }
}