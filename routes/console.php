<?php

use Illuminate\Support\Facades\Schedule;

// auto log out logic
Schedule::command('app:update-open-time-logs')
    ->cron('0 10,12,15 * * *')  // Runs at 10am, 12:00pm, and 3:00pm
    ->timezone('Asia/Manila');

// salary paylslip computation
Schedule::command('salary:compute')
    ->twiceMonthly(1, 16, '01:00')
    ->timezone('Asia/Manila');

Schedule::command('attendance:compute')
    ->twiceMonthly(1, 16, '01:30')
    ->timezone('Asia/Manila');