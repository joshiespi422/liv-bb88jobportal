<?php

namespace App\Services;

use App\Models\Holiday;
use App\Models\Salary;
use App\Models\Status;
use Carbon\Carbon;

class SalaryCalculationService
{
    public function compute($users, $period)
    {
        $start = Carbon::parse($period->start_date);
        $end = Carbon::parse($period->end_date);

        // 1. Get all standard workdays (Mon-Sat)
        $workdaysInPeriod = [];
        $tempDate = $start->copy();
        while ($tempDate->lte($end)) {
            if (!$tempDate->isSunday()) {
                $workdaysInPeriod[] = $tempDate->toDateString();
            }
            $tempDate->addDay();
        }
        $totalDaysInPeriod = count($workdaysInPeriod);

        $holidaysInRange = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])->get();
        $pendingStatusId = Status::where('status_name', 'pending')->value('id');

        foreach ($users as $user) {
            $currentSalary = $user->employeeDetails->current_salary;
            $halfSalary = $currentSalary / 2;
            
            // Daily rate based on actual workdays in this specific period
            $dailyRate = $totalDaysInPeriod > 0 ? $halfSalary / $totalDaysInPeriod : 0;
            $hourlyRate = $dailyRate / 8;

            $holidayPivotData = [];
            $holidayAmountTotal = 0;
            
            // Get unique dates user actually logged in
            $userLogs = $user->timeLogs()
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->unique()
                ->toArray();

            // --- HOLIDAY CALCULATION ---
            foreach ($holidaysInRange as $holiday) {
                $hasWorked = in_array($holiday->date, $userLogs);
                $currentHolidayPay = 0;
                $pivotAmount = null;               

                if ($holiday->type === 'regular') {
                    // IF WORKED: 200% (Base 100% + Extra 100%)
                    // IF NOT WORKED: 100% (Base 100% + Extra 0%)
                    $multiplier = $hasWorked ? 2.0 : 1.0;
                    $currentHolidayPay = $dailyRate * $multiplier;
                    // In pivot: 1.0 if worked, 0.00 if not worked
                    $pivotAmount = $hasWorked ? $dailyRate : 0.00;
                } elseif ($holiday->type === 'special') {
                    if ($hasWorked) {
                        // IF WORKED: 30% (Base 100% + Extra 30%)
                        $currentHolidayPay = $dailyRate * 0.30;
                        $pivotAmount = $currentHolidayPay;
                    } else {
                        // IF NOT WORKED: 0%
                        $currentHolidayPay = 0;
                        $pivotAmount = 0.00;
                    }
                }
                // Always record the holiday in the pivot data
                $holidayPivotData[$holiday->id] = [
                    'amount' => $pivotAmount !== null ? round($pivotAmount, 2) : null
                ];
                // Total holiday pay used for Gross Pay calculation logic
                $holidayAmountTotal += $currentHolidayPay;
            }

            // --- ABSENCE CALCULATION ---
            // A user is only "Absent" if they missed a day that was NOT a Regular Holiday
            $regularHolidayDates = $holidaysInRange->where('type', 'regular')->pluck('date')->toArray();
            $requiredWorkDates = array_diff($workdaysInPeriod, $regularHolidayDates);
            
            $actualDaysWorked = count(array_intersect($userLogs, $requiredWorkDates));
            $absentDays = max(0, count($requiredWorkDates) - $actualDaysWorked);
            $absentDeduction = $absentDays * $dailyRate;

            // --- OVERTIME ---
            $totalOTHours = $user->overtimesRequested()
                ->where('status_id', Status::where('status_name', 'approved')->value('id'))
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->sum('total_hours');
            $overtimeAmount = $totalOTHours * $hourlyRate;

            // --- FINAL TOTALS ---
            // Gross = (Base - Absences) + Holiday Pay + OT
            // We subtract all holidays from base because we added the 100% or 200% back in $holidayAmount
            $baseWorkPay = (count($requiredWorkDates) * $dailyRate); 
            $grossPay = $baseWorkPay + $holidayAmountTotal + $overtimeAmount;
            $netPay = $grossPay - $absentDeduction;

            $salary = Salary::updateOrCreate(
                ['user_id' => $user->id, 'salary_period_id' => $period->id],
                [
                    'status_id' => $pendingStatusId,
                    'rate_day' => round($dailyRate, 2),
                    'rate_month' => $currentSalary,
                    'absent_day' => $absentDays,
                    'absent_deduction' => round($absentDeduction, 2),
                    'overtime_hour' => $totalOTHours,
                    'overtime_amount' => round($overtimeAmount, 2),
                    'holiday_amount' => round($holidayAmountTotal, 2),
                    'gross_pay' => round($grossPay, 2),
                    'net_pay' => round($netPay, 2), // Add tax/SSS deductions here if needed
                ]
            );

            $salary->holidays()->sync($holidayPivotData);
        }
    }
}