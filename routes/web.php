<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InternController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AccomplishmentController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\HolidayController;
use Illuminate\Support\Facades\Route;

// Profile info for the qr code
Route::get('/info', [ProfileController::class, 'showInfo'])->name('info');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:1,2')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/time-in', [DashboardController::class, 'store'])->name('timein');
    Route::post('/time-out/check', [DashboardController::class, 'check'])->name('timeout.check');
    Route::patch('/time-out/{timeLog}', [DashboardController::class, 'update'])->name('timeout.update');

    Route::middleware('user.type:super_admin,employee')->group(function () {
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/employees', [EmployeeController::class, 'index'])->name('employees');
            Route::get('/employees/{id}', [EmployeeController::class, 'show'])->name('employees.show');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store'); 
            Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        });
    });

    Route::middleware(['user.type:super_admin,intern,employee', 'employee.hierarchy:Leader'])->group(function () {
        Route::prefix('team')->name('team.')->group(function () {
            Route::get('/interns', [InternController::class, 'index'])->name('interns');
            Route::get('/interns/{id}', [InternController::class, 'show'])->name('interns.show');
            Route::post('/interns', [InternController::class, 'store'])->name('interns.store');
            Route::patch('/interns/{intern}', [InternController::class, 'update'])->name('interns.update');
        });
    });
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::middleware('user.type:super_admin')->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
        Route::get('/attendance/user/{id}/{date}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::get('/attendance/dept/{deptId}/{date}', [AttendanceController::class, 'showDeptLog'])->name('attendance.show.dept');
        Route::post('/department', [DepartmentController::class, 'store'])->name('department.store');
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
    Route::patch('/accomplishment/{accomplishment}', [AccomplishmentController::class, 'update'])->name('accomplishment.update');
    Route::post('/accomplishment/export', [AccomplishmentController::class, 'export'])->name('accomplishment.export');

    Route::get('/project', [ProjectController::class, 'index'])->name('project');
    Route::get('/project/{id}', [ProjectController::class, 'show'])->name('project.show');
    Route::post('/project', [ProjectController::class, 'store'])->name('project.store');
    Route::get('/project-issue/{issue}', [ProjectController::class, 'showIssue'])->name('project.issue.show');
    Route::post('/project-issue', [ProjectController::class, 'storeIssue'])->name('project.issue.store');
    Route::patch('/project-issue/{issue}', [ProjectController::class, 'resolveIssue'])->name('project.issue.resolve');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile/picture', [ProfileController::class, 'updatePicture'])->name('profile.picture.update');
    Route::delete('/profile/picture', [ProfileController::class, 'deletePicture'])->name('profile.picture.delete');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/details', [ProfileController::class, 'updateDetails'])->name('profile.details.update');

    Route::post('/comment', [CommentController::class, 'store'])->name('comment.store');

    Route::get('/notification', [NotificationController::class, 'index'])->name('notification');
    Route::get('/notification/latest', [NotificationController::class, 'latest'])->name('notification.latest');
    Route::put('/notification/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notification.markAllAsRead');
    Route::delete('/notification/destroy-all', [NotificationController::class, 'destroyAll'])->name('notification.destroyAll');
    Route::delete('/notification/{notification}', [NotificationController::class, 'destroy'])->name('notification.destroy');
    Route::patch('/notification/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notification.markAsRead');
    
    Route::middleware('user.type:super_admin,employee')->group(function () {
        Route::get('/leave', [LeaveController::class, 'index'])->name('leave');
        Route::get('/leave/{id}', [LeaveController::class, 'show'])->name('leave.show');
        Route::get('/leave/categories/{leaveTypeId}', [LeaveController::class, 'fetchCategories'])->name('leave.categories');
        Route::post('/leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::post('/leave/{leave}/validate', [LeaveController::class, 'validateLeave'])->name('leave.validate');
        
        Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
        Route::get('/salary/payroll/{period}', [SalaryController::class, 'payrollList'])->name('salary.payroll');
        Route::get('/salary/{id}/{period}', [SalaryController::class, 'show'])->name('salary.show');
        Route::patch('/salary/{salary}/approve', [SalaryController::class, 'approve'])->name('salary.approve');
        Route::post('/salary/recompute/single', [SalaryController::class, 'recompute'])->name('salary.recompute.single');
        Route::post('/salary/recompute/all', [SalaryController::class, 'recomputeAll'])->name('salary.recompute.all');

        Route::get('/overtime', [OvertimeController::class, 'index'])->name('overtime');
        Route::get('/overtime/{id}', [OvertimeController::class, 'show'])->name('overtime.show');
        Route::post('/overtime', [OvertimeController::class, 'store'])->name('overtime.store');
        Route::patch('/overtime/{overtime}/sign', [OvertimeController::class, 'signOvertime'])->name('overtime.sign');
        Route::patch('/overtime/{overtime}/validate', [OvertimeController::class, 'validateOvertime'])->name('overtime.validate');

        Route::get('/holiday', [HolidayController::class, 'index'])->name('holiday');
    });

    Route::middleware('user.type:super_admin,employee')->group(function () {
        Route::get('/material-request', [MaterialRequestController::class, 'index'])->name('material.request');
        Route::get('/material-request/{id}', [MaterialRequestController::class, 'show'])->name('material.request.show');
        Route::post('/material-request', [MaterialRequestController::class, 'store'])->name('material.request.store');
        Route::patch('/material-request/{materialRequest}/sign', [MaterialRequestController::class, 'signMaterialRequest'])->name('material.request.sign');
        Route::patch('/material-request/{materialRequest}/validate', [MaterialRequestController::class, 'validateMaterialRequest'])->name('material.request.validate');
    });
});



