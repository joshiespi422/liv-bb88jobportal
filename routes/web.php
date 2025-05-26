<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('DashboardView');
})->name('dashboard');

Route::prefix('attendance')->name('attendance.')->group(function () {
    Route::get('/today', function () {
        return Inertia::render('TodayView');
    })->name('today');

    Route::get('/timelogs', function () {
        return Inertia::render('TimeLogsView');
    })->name('timelogs');
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
