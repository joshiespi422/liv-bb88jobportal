<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class
        ]);

        $middleware->alias([
            'user.type' => \App\Http\Middleware\CheckUserType::class, 
            'employee.hierarchy' => \App\Http\Middleware\CheckEmployeeHierarchy::class
        ]);

    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('logs:update-open')
                 ->cron('0 10,12,15 * * *') // Runs at 10:00, 12:00, and 15:00
                 ->timezone('Asia/Manila');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
