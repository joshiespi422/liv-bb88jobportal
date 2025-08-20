<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
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

            // Query for Online Users
            $onlineUsers = User::whereHas('timeLogs', function ($query) {
                $query->whereDate('date', now()->toDateString())
                      ->whereNotNull('time_in')
                      ->whereNull('time_out');
            })
            ->with(['employeeDetails.department', 'internDetails.department'])
            ->get()
            ->map(function ($onlineUser) {
                // Determine department from either employee or intern details
                $department = 'N/A';
                if ($onlineUser->employeeDetails && $onlineUser->employeeDetails->department) {
                    $department = $onlineUser->employeeDetails->department->dept_name;
                } elseif ($onlineUser->internDetails && $onlineUser->internDetails->department) {
                    $department = $onlineUser->internDetails->department->dept_name;
                }

                return [
                    'name'       => $onlineUser->name,
                    'position'   => $onlineUser->position,
                    'department' => $department,
                    'status'     => 'Online',
                ];
            });
            
            $props['onlineUsers'] = $onlineUsers;

            // --- LOGIC FOR MAP ---
            // 1. Get the list of users who have time logs today for the filter dropdown.
            $usersWithLogsToday = User::whereHas('timeLogs', function ($query) {
                    $query->whereDate('date', Carbon::today())
                          ->whereNotNull(['latitude', 'longitude']);
                })
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
            
            // Prepend "All Users" option for the combobox
            $props['usersForMapFilter'] = $usersWithLogsToday->prepend(['id' => 'all', 'name' => 'All Users']);
            
            // 2. Get location data based on the request filter (user_id).
            $selectedUserId = $request->input('user_id', 'all');

            $query = TimeLog::with(['user.employeeDetails.department', 'user.internDetails.department'])
                ->whereDate('date', Carbon::today())
                ->whereNotNull(['latitude', 'longitude']);

            if ($selectedUserId === 'all') {
                // LOGIC 1: Get the LATEST time-in for EACH user today.
                // We get all logs, sort them descendingly by time, group by user, and take the first one of each group.
                $allLogsToday = $query->orderBy('time_in', 'desc')->get();
                $latestLogs = $allLogsToday->groupBy('user_id')->map(function ($group) {
                    return $group->first();
                })->values();

            } else {
                // LOGIC 2: Get ALL time-ins for a SPECIFIC user today.
                $latestLogs = $query->where('user_id', $selectedUserId)->orderBy('time_in', 'asc')->get();
            }

            // 3. Format the data for the map component.
            $props['timeLogLocations'] = $latestLogs->map(function ($log) {
                $department = 'N/A';
                if ($log->user) {
                    if ($log->user->employeeDetails && $log->user->employeeDetails->department) {
                        $department = $log->user->employeeDetails->department->dept_name;
                    } elseif ($log->user->internDetails && $log->user->internDetails->department) {
                        $department = $log->user->internDetails->department->dept_name;
                    }
                }

                return [
                    'user_id'    => $log->user_id,
                    'name'       => $log->user->name ?? 'Unknown',
                    'position'   => $log->user->position ?? 'N/A',
                    'department' => $department,
                    'time_in'    => $log->time_in,
                    'latitude'   => (float) $log->latitude,
                    'longitude'  => (float) $log->longitude,
                ];
            });

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
                    'firstIn' => 'N/A',
                    'secondIn' => 'N/A',
                    'thirdIn' => 'N/A',
                    'fourthIn' => 'N/A',
                    'lastOut' => 'N/A',
                ];

                $timeIns = $dailyLogs->pluck('time_in')->filter()->values();

                // Logic if user has a full 4 time-ins
                if ($timeIns->count() === 4) {
                    $record['firstIn'] = $timeIns[0];
                    $record['secondIn'] = $timeIns[1];
                    $record['thirdIn'] = $timeIns[2];
                    $record['fourthIn'] = $timeIns[3];
                } 
                // Logic for incomplete time-ins, placed in specific windows
                else {
                    foreach ($timeIns as $timeIn) {
                        if ($timeIn < '10:01:00') {
                            $record['firstIn'] = $timeIn;
                        } elseif ($timeIn >= '10:14:00' && $timeIn < '12:01:00') {
                            $record['secondIn'] = $timeIn;
                        } elseif ($timeIn >= '12:59:00' && $timeIn < '15:01:00') {
                            $record['thirdIn'] = $timeIn;
                        } elseif ($timeIn >= '15:14:00') {
                            $record['fourthIn'] = $timeIn;
                        }
                    }
                }

                // Get the latest time_out for the day
                $lastOut = $dailyLogs->pluck('time_out')->filter()->max();
                if ($lastOut) {
                    $record['lastOut'] = $lastOut;
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
