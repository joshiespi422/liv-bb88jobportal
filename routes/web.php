<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InternController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AccomplishmentController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ProjectController;
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

    Route::middleware('user.type:super_admin,employee')->group(function () {
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees');
            Route::get('/employees/{id}', [EmployeeController::class, 'show'])->name('employees.show');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store'); 
        });
    });

    Route::middleware('user.type:super_admin,intern')->group(function () {
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/interns', [InternController::class, 'index'])->name('interns');
            Route::get('/interns/{id}', [InternController::class, 'show'])->name('interns.show');
            Route::post('/interns', [InternController::class, 'store'])->name('interns.store');
        });
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
    
    Route::get('/task', [TaskController::class, 'index'])->name('task');
    Route::get('/task/{id}', [TaskController::class, 'show'])->name('task.show');
    Route::post('/task/{task}/update', [TaskController::class, 'updateTask'])->name('task.update');
    Route::post('/task/{task}/validate', [TaskController::class, 'validateTask'])->name('task.validate');
    Route::post('/task', [TaskController::class, 'store'])->name('task.store');
    Route::get('/task/assignees/{department}', [TaskController::class, 'fetchAssignees'])->name('task.assignees');
    Route::get('/task/projects/{department}', [TaskController::class, 'fetchProjects'])->name('task.projects');

    Route::get('/accomplishment', [AccomplishmentController::class, 'index'])->name('accomplishment');
    Route::get('/accomplishment/{id}', [AccomplishmentController::class, 'show']);
    Route::put('/accomplishment/{accomplishment}', [AccomplishmentController::class, 'update'])->name('accomplishment.update');

    Route::get('/project', [ProjectController::class, 'index'])->name('project');
    Route::get('/project/{id}', [ProjectController::class, 'show'])->name('project.show');
    Route::post('/project', [ProjectController::class, 'store'])->name('project.store');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile/picture', [ProfileController::class, 'updatePicture'])->name('profile.picture.update');
    Route::delete('/profile/picture', [ProfileController::class, 'deletePicture'])->name('profile.picture.delete');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/details', [ProfileController::class, 'updateDetails'])->name('profile.details.update');

    Route::middleware('user.type:super_admin,employee')->group(function () {
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave');
        Route::get('/leave/{id}', [LeaveController::class, 'show']);
        Route::get('/leave/categories/{leaveTypeId}', [LeaveController::class, 'fetchCategories'])->name('leave.categories');
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::post('/leave/{leave}/validate', [LeaveController::class, 'validateLeave'])->name('leave.validate');
    });
    
});



