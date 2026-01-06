<?php

use Illuminate\Support\Facades\Schedule;

// auto log out logic
Schedule::command('app:update-open-time-logs')
    ->cron('0 10,12,15 * * *')  // Runs at 10am, 12:00pm, and 3:00pm
    ->timezone('Asia/Manila');

