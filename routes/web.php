<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return Inertia::render('DashboardView');
    })->name('dashboard');

    Route::prefix('team')->name('team.')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees');
        Route::get('/employees/{id}', [EmployeeController::class, 'show'])->name('employees.show');
    });
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::middleware('user.type:super_admin')->group(function () {
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/today', function () {
                return Inertia::render('TodayView');
            })->name('today');

            Route::get('/timelogs', function () {
                return Inertia::render('TimeLogsView');
            })->name('timelogs');
        });
    });

    Route::get('profile', function () {
        return Inertia::render('ProfileView');
    })->name('profile');

    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/regular', function () {
            return Inertia::render('RegularLeaveView');
        })->name('regular');

        Route::get('/special', function () {
            return Inertia::render('SpecialLeaveView');
        })->name('special');
    });
});



