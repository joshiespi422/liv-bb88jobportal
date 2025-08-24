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
use App\Http\Requests\StoreTimeInRequest;
use App\Http\Requests\CheckTimeOutRequest;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing(['userType']);
        $userType = $user->userType->type_name;

        $props = [];

        // SUPER ADMIN
        if ($userType === 'super_admin') {
           // Eagerly load props that are always needed on initial visit
            $props['totalCounts'] = $this->getTotalCounts();
            $props['usersForMapFilter'] = $this->getUsersForMapFilter();
            $props['onlineUsers'] = $this->getOnlineUsers();
            $props['timeLogLocations'] = $this->getTimeLogLocations($request);
        } else {
            // Add props for other user types (employee, intern)
            $props['userDetails'] = $this->getUserDetails($user, $userType);
            $props['attendanceList'] = $this->getAttendanceList($user);  
        }

        return Inertia::render('DashboardView', $props);
    }
    
    // --- HELPER METHODS FOR SUPER ADMIN LOGIC ----
    // Get the total counts of users, tasks, and accomplishments
    private function getTotalCounts(): array
    {
        return [
            'users' => User::count(),
            'tasks' => Task::count(),
            'accomplishments' => Accomplishment::count(),
        ];
    }
    // Get the list of online users
    private function getOnlineUsers(): Collection
    {
        return User::whereHas('timeLogs', function ($query) {
                $query->whereDate('date', now()->toDateString())
                      ->whereNotNull('time_in')
                      ->whereNull('time_out');
            })
            ->with(['employeeDetails.department', 'internDetails.department'])
            ->get()
            ->map(function ($onlineUser) {
                $department = 'N/A';
                if ($onlineUser->employeeDetails && $onlineUser->employeeDetails->department) {
                    $department = $onlineUser->employeeDetails->department->dept_name;
                } elseif ($onlineUser->internDetails && $onlineUser->internDetails->department) {
                    $department = $onlineUser->internDetails->department->dept_name;
                }
                return [
                    'name'       => $onlineUser->name,
                    'position'   => $onlineUser->position ?? 'N/A',
                    'department' => $department,
                    'status'     => 'Online',
                ];
            });
    }
    // Get the list of users for the map filter
    private function getUsersForMapFilter(): Collection
    {
        $users = User::whereHas('timeLogs', function ($query) {
                $query->whereDate('date', Carbon::today())
                      ->whereNotNull(['latitude', 'longitude']);
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return $users->prepend(['id' => 'all', 'name' => 'All Users']);
    }
    // Get the list of time logs for the map
    private function getTimeLogLocations(Request $request): Collection
    {
        $selectedUserId = $request->input('user', 'all');

        $query = TimeLog::with(['user.employeeDetails.department', 'user.internDetails.department'])
            ->whereDate('date', Carbon::today())
            ->whereNotNull(['latitude', 'longitude']);

        // Filter by user from the URL query parameter
        if ($selectedUserId === 'all' || $selectedUserId === null) {
            $allLogsToday = $query->orderBy('time_in', 'desc')->get();
            $latestLogs = $allLogsToday->groupBy('user_id')->map->first()->values();
        } else {
            $latestLogs = $query->where('user_id', $selectedUserId)->orderBy('time_in', 'asc')->get();
        }

        // Map the logs to the desired format
        return $latestLogs->map(function ($log) {
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
    }

    // --- PRIVATE HELPER METHODS FOR EMPLOYEE/INTERN ---
    // Get user details and his time logs
    private function getUserDetails(User $user, string $userType): array
    {
        $relation = match($userType) {
                'employee' => 'employeeDetails.department',
                'intern' => 'internDetails.department',
                default => null
            };

            // Eager load only necessary relationships
            if ($relation) {
                $user->loadMissing($relation);
            }
            
            // Get today's time logs in single query
            $todayLogs = TimeLog::where('user_id', $user->id)
                ->whereDate('date', now())
                ->get();
            
            return [
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
    }
    // Get user's attendance list for the last 30 days
    private function getAttendanceList(User $user): array
    {
        $timeLogs = TimeLog::where('user_id', $user->id)
                ->where('date', '>=', now()->subDays(30)->toDateString())
                ->orderBy('date', 'desc')
                ->orderBy('time_in', 'asc') // Order by time_in is crucial for sequential mapping
                ->get();

            // Group the logs by date
            $logsByDate = $timeLogs->groupBy('date');

            // Process each day's logs to create the desired format
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
            
            return $attendanceList->values()->all();
    }

    /**
     * Check if a user can time out and get the specific log ID.
     */
    public function check(CheckTimeOutRequest $request)
    {
        // Validation passed, now find the specific log to get its ID
        $openLog = $request->user()->timeLogs()
            ->where('date', Carbon::today()->toDateString())
            ->whereNull('time_out')
            ->latest('time_in')
            ->first();

        $currentTime = now()->format('h:i A');
        $baseMessage = "This will be your final time out for today. You won't be able to time in again. Are you sure you want to proceed?";

        return response()->json([
            'needsConfirmation' => true,
            'message' => "It's {$currentTime}. {$baseMessage}",
            'timeLogId' => $openLog->id,
        ]);
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
    public function store(StoreTimeInRequest $request)
    {
        $user = $request->user();

        // The request has already been validated and passed all business rules
        $user->timeLogs()->create([
            'time_in' => now()->format('H:i:s'),
            'date' => now()->toDateString(),
            'ip_address' => $request->ip(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return back()->with('success', 'Time in recorded successfully!');
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
    public function update(Request $request, TimeLog $timeLog)
    {        
        // Authorization check
        if ($timeLog->user_id !== Auth::id()) {
            abort(403, 'not authorized');
        } elseif ($timeLog->time_out !== null) {
            abort(403, 'already timed out');
        }
       
        $timeLog->update(['time_out' => now()]);

        return back()->with('success', 'You have been timed out successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
