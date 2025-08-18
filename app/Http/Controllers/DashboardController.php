<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
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
                'picture' => $user->picture
                    ? Storage::url($user->picture)
                    : Storage::url('profile-images/default.png'),
                'time_in' => $todayLogs->pluck('time_in')->filter()->values(),
                'time_out' => $todayLogs->pluck('time_out')->filter()->values(),
                'date' => now()->format('Y-m-d'),
                'status' => $todayLogs->contains(fn($log) => $log->time_in && !$log->time_out) 
                    ? 'Online' 
                    : 'Offline',
            ];

            $props['userDetails'] = $userDetails;

            // 1. Fetch all time logs for the last 60 days in one query
            $timeLogs = TimeLog::where('user_id', $user->id)
                ->where('date', '>=', now()->subDays(30)->toDateString())
                ->orderBy('date', 'desc')
                ->orderBy('time_in', 'asc') // Order by time_in is crucial for sequential mapping
                ->get();

            // 2. Group the logs by date
            $logsByDate = $timeLogs->groupBy('date');

            // 3. Process each day's logs to create the desired format
            $attendanceList = $logsByDate->map(function (Collection $dailyLogs, $date) {
                $record = [
                    'date' => $date,
                    '1stIn' => 'N/A',
                    '2ndIn' => 'N/A',
                    '3rdIn' => 'N/A',
                    '4thIn' => 'N/A',
                    'LastOut' => 'N/A',
                ];

                $timeIns = $dailyLogs->pluck('time_in')->filter()->values();

                // Logic if user has a full 4 time-ins
                if ($timeIns->count() === 4) {
                    $record['1stIn'] = $timeIns[0];
                    $record['2ndIn'] = $timeIns[1];
                    $record['3rdIn'] = $timeIns[2];
                    $record['4thIn'] = $timeIns[3];
                } 
                // Logic for incomplete time-ins, placed in specific windows
                else {
                    foreach ($timeIns as $timeIn) {
                        if ($timeIn < '10:01:00') {
                            $record['1stIn'] = $timeIn;
                        } elseif ($timeIn >= '10:14:00' && $timeIn < '12:01:00') {
                            $record['2ndIn'] = $timeIn;
                        } elseif ($timeIn >= '12:59:00' && $timeIn < '15:01:00') {
                            $record['3rdIn'] = $timeIn;
                        } elseif ($timeIn >= '15:14:00') {
                            $record['4thIn'] = $timeIn;
                        }
                    }
                }

                // Get the latest time_out for the day
                $lastOut = $dailyLogs->pluck('time_out')->filter()->max();
                if ($lastOut) {
                    $record['LastOut'] = $lastOut;
                }

                return $record;
            });
            
            // Add the formatted list to props, ensuring it's a plain array
            $props['attendanceList'] = $attendanceList->values()->all();
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
