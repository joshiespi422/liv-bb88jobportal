<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Task;
use App\Models\Accomplishment;
use App\Models\TimeLog;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->load(['userType']);
        $userType = $user->userType->type_name;

        $props = [];

        // SUPER ADMIN
        if ($userType === 'super_admin') {
            $props['totalCounts'] = [
                'users' => User::count(),
                'tasks' => Task::count(),
                'accomplishments' => Accomplishment::count(),
            ];
        } else {
            // Determine relationship based on user type
            $relation = match($userType) {
                'employee' => 'employeeDetails.department',
                'intern' => 'internDetails.department',
                default => null
            };

            // Eager load only necessary relationships
            if ($relation) {
                $user->load($relation);
            }
            
            // Get today's time logs in single query
            $todayLogs = TimeLog::where('user_id', $user->id)
                ->whereDate('date', now())
                ->get();

            
            $userDetails = [
                'name' => $user->name,
                'department' => match($userType) {
                        'employee' => $user->employeeDetails->department->dept_name,
                        'intern' => $user->internDetails->department->dept_name,
                        default => 'N/A'
                    },
                'time_in' => $todayLogs->pluck('time_in')->filter()->values(),
                'time_out' => $todayLogs->pluck('time_out')->filter()->values(),
                'date' => now()->format('Y-m-d'),
                'status' => $todayLogs->contains(fn($log) => $log->time_in && !$log->time_out) 
                    ? 'Online' 
                    : 'Offline',
            ];

            $props['userDetails'] = $userDetails;
        }

        return Inertia::render('DashboardView', $props);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
