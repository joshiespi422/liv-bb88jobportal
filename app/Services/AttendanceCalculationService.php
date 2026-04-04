<?php

namespace App\Services;

use App\Models\AttendanceReport;
use App\Models\Holiday;
use App\Models\Status;
use Carbon\Carbon;

class AttendanceCalculationService
{
    /**
     * Standard shift start. Anything after this is considered late.
     */
    private const SHIFT_START = '08:00:00';
    private const HOURS_PER_DAY = 8;
    /**
     * Fraction threshold for rounding holiday contributions up.
     * e.g. 1.8 → 2, but 1.3 stays 1.3
     */
    private const HOLIDAY_ROUND_THRESHOLD = 0.8;

    public function compute($users, $period): void
    {
        $start = Carbon::parse($period->start_date);
        $end   = Carbon::parse($period->end_date);

        // ── 1. All standard workdays (Mon–Sat) — no exclusions ──────────────
        // This is what populates the "day" column: the raw period length.
        $workdaysInPeriod = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (! $cursor->isSunday()) {
                $workdaysInPeriod[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }
        $totalDaysInPeriod = count($workdaysInPeriod); // stored in "day"

        // ── 2. Holidays & required workdays ─────────────────────────────────
        $holidaysInRange = Holiday::whereBetween('date', [
            $start->toDateString(),
            $end->toDateString(),
        ])->get();

        $regularHolidayDates = $holidaysInRange
            ->where('type', 'regular')
            ->pluck('date')
            ->toArray();

        // Required = workdays the user is actually expected to appear
        // (regular holidays are auto-credited, so excluded from absence check)
        $requiredWorkDates = array_values(
            array_diff($workdaysInPeriod, $regularHolidayDates)
        );

        $approvedStatusId = Status::where('status_name', 'approved')->value('id');
        $shiftStart       = Carbon::createFromFormat('H:i:s', self::SHIFT_START);

        // ── 3. Per-user computation ──────────────────────────────────────────
        foreach ($users as $user) {

            // Earliest time-in per day within the period
            $dailyFirstLog = $user->timeLogs()
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('time_in')
                ->get()
                ->groupBy('date')
                ->map(fn ($logs) => $logs->first());

            $userLogDates = $dailyFirstLog->keys()->toArray();

            // ── ABSENT ───────────────────────────────────────────────────────
            $daysPresent = count(array_intersect($userLogDates, $requiredWorkDates));
            $absentDays  = max(0, count($requiredWorkDates) - $daysPresent);

            // ── LATES ────────────────────────────────────────────────────────
            // Accumulate raw minutes; divide once at the end to avoid float drift
            $totalLateMinutes = 0;
            foreach ($dailyFirstLog as $date => $log) {
                if (! in_array($date, $requiredWorkDates)) {
                    continue;
                }
                $timeIn = Carbon::createFromFormat('H:i:s', $log->time_in);
                if ($timeIn->gt($shiftStart)) {
                    $totalLateMinutes += (int) $shiftStart->diffInMinutes($timeIn);
                }
            }
            $hours     = intdiv($totalLateMinutes, 60);
            $minutes   = $totalLateMinutes % 60;
            $lateHours = round($hours + ($minutes / 100), 2);

            // ── HOLIDAYS ─────────────────────────────────────────────────────
            // Regular  → +1.0 day (credited regardless of attendance)
            // Special  → +0.3 day only when the user was present
            $holidayContribution = 0.0;
            $holidayPivotIds     = [];

            foreach ($holidaysInRange as $holiday) {
                $workedOnHoliday = in_array($holiday->date, $userLogDates);

                if ($holiday->type === 'regular' && $workedOnHoliday) {
                    $holidayContribution           += 1.0;
                    $holidayPivotIds[$holiday->id] = [];
                } elseif ($holiday->type === 'special' && $workedOnHoliday) {
                    $holidayContribution           += 0.3;
                    $holidayPivotIds[$holiday->id]  = [];
                }
            }

            // Round up only when the fraction meets the threshold (e.g. 1.8 → 2)
            $fraction = $holidayContribution - floor($holidayContribution);
            if ($fraction > 0 && $fraction >= self::HOLIDAY_ROUND_THRESHOLD) {
                $holidayContribution = ceil($holidayContribution);
            }

            // ── OVERTIME ─────────────────────────────────────────────────────
            // Sum all approved OT hours in the period; convert to fractional days
            $overtimeHours = (float) $user->overtimesRequested()
                ->where('status_id', $approvedStatusId)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->sum('total_hours');

            $overtimeDays = $overtimeHours / self::HOURS_PER_DAY;

            // ── TOTAL ────────────────────────────────────────────────────────
            // Base workdays + holiday credit + overtime days − absent days
            // Halfday deduction deferred (no table yet)
            // Lates are tracked but not deducted from total yet
            $rawTotal = $totalDaysInPeriod
                + $holidayContribution
                + $overtimeDays
                - $absentDays;

            // Apply the same 0.8 threshold rule to the final total:
            //   14.63 → fraction 0.63 < 0.8 → round to 1 decimal → 14.6
            //   13.38 → fraction 0.38 < 0.8 → round to 1 decimal → 13.4
            //   13.80 → fraction 0.80 >= 0.8 → ceil → 14
            $totalFraction = $rawTotal - floor($rawTotal);
            $total = $totalFraction >= self::HOLIDAY_ROUND_THRESHOLD
                ? (float) ceil($rawTotal)
                : round($rawTotal, 1);

            // ── PERSIST ──────────────────────────────────────────────────────
            $report = AttendanceReport::updateOrCreate(
                [
                    'user_id'          => $user->id,
                    'salary_period_id' => $period->id,
                ],
                [
                    'day'     => $totalDaysInPeriod,   // raw workday count, never changes
                    'absent'  => $absentDays,
                    'halfday' => 0,
                    'overtime' => $overtimeHours,       // stored in hours for display
                    'lates'   => $lateHours,
                    'total'   => $total,
                ]
            );

            $report->holidays()->sync($holidayPivotIds);
        }
    }

}