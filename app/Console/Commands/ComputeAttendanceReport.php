<?php

namespace App\Console\Commands;

use App\Models\SalaryPeriod;
use App\Models\User;
use App\Services\AttendanceCalculationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ComputeAttendanceReport extends Command
{
    protected $signature = 'attendance:compute';

    protected $description = 'Compute bi-monthly employee attendance reports';

    public function handle(AttendanceCalculationService $service): void
    {
        $today = Carbon::today();

        // Mirror the date logic from ComputeSalary so periods always align
        [$start, $end] = match (true) {
            $today->day === 16 => [
                $today->copy()->startOfMonth(),
                $today->copy()->day(15),
            ],
            $today->day === 1 => [
                $today->copy()->subMonth()->day(16),
                $today->copy()->subMonth()->endOfMonth(),
            ],
            default => [null, null],
        };

        if (! $start) {
            $this->warn('Attendance computation only runs on the 1st and 16th.');
            return;
        }

        // The period is already created by ComputeSalary (runs 30 min earlier at 01:00)
        $period = SalaryPeriod::where('start_date', $start->toDateString())
            ->where('end_date', $end->toDateString())
            ->first();

        if (! $period) {
            $this->error("Salary period not found ({$start->toDateString()} – {$end->toDateString()}). Ensure salary:compute ran first.");
            return;
        }

        $users = User::whereHas('status', fn ($q) => $q->where('status_name', 'active'))
            ->whereHas('employeeDetails')
            ->get();

        $service->compute($users, $period);

        $this->info("Attendance report computed for {$period->month} {$period->year} — {$period->cycle} cycle.");
    }
}