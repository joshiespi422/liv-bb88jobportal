<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TimeLog;
use Carbon\Carbon;

class TimeLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the time slots
        $timeSlots = [
            ['time_in' => '08:00:00', 'time_out' => '10:00:00'],
            ['time_in' => '10:15:00', 'time_out' => '12:00:00'],
            ['time_in' => '13:00:00', 'time_out' => '15:00:00'],
            ['time_in' => '15:15:00', 'time_out' => '17:00:00'],
        ];

        // Get all users except super_admin
        $users = User::whereHas('userType', function ($query) {
            $query->where('type_name', '!=', 'super_admin');
        })->get();

        // Generate dates from today back to 3 weeks, excluding Sundays
        $dates = $this->generateDates();

        foreach ($users as $user) {
            // 70% chance this user will have logs
            $hasLogs = rand(1, 100) <= 70;

            if (!$hasLogs) {
                continue; // Skip this user entirely
            }

            foreach ($dates as $date) {
                // 70% chance the user has logs for this specific day
                $hasLogsForDay = rand(1, 100) <= 70;

                if (!$hasLogsForDay) {
                    continue; // Skip this day for this user
                }

                // Create time logs for each time slot
                foreach ($timeSlots as $slot) {
                    TimeLog::create([
                        'user_id' => $user->id,
                        'time_in' => $slot['time_in'],
                        'time_out' => $slot['time_out'],
                        'date' => $date,
                        'ip_address' => $this->generateRandomIp(),
                        'latitude' => $this->generateRandomLatitude(),
                        'longitude' => $this->generateRandomLongitude(),
                    ]);
                }
            }
        }
    }

    /**
     * Generate dates from today back to 3 weeks, excluding Sundays
     */
    private function generateDates(): array
    {
        $dates = [];
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subWeeks(3);

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Exclude Sundays (0 = Sunday in Carbon)
            if ($currentDate->dayOfWeek !== Carbon::SUNDAY) {
                $dates[] = $currentDate->format('Y-m-d');
            }
            $currentDate->addDay();
        }

        return $dates;
    }

    /**
     * Generate a random IP address
     */
    private function generateRandomIp(): string
    {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
    }

    /**
     * Generate a random latitude inside Angeles City, Pampanga
     */
    private function generateRandomLatitude(): float
    {
        // Angeles City latitude range: 15.12 to 15.20
        return round(rand(15120, 15200) / 1000, 6);
    }

    /**
     * Generate a random longitude inside Angeles City, Pampanga
     */
    private function generateRandomLongitude(): float
    {
        // Angeles City longitude range: 120.55 to 120.65
        return round(rand(120550, 120650) / 1000, 6);
    }

}