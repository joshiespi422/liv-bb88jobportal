<?php

namespace App\Http\Controllers;

use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // authorization check
        if ($request->user()->userType->type_name !== 'super_admin') {
            abort(403, 'not authorized');
        }

        // determine the log type from the URL, defaulting to 'today'.
        $logType = $request->query('tab', 'today');
        $props = [
            'activeTab' => $logType
        ];

        if ($logType === 'all') {
            $props['deptAttendance'] = $this->getDeptAttendance();
        } else {
            $props['todayList'] = $this->getTodayList();
        }

        // 4. Render the Inertia view with the fetched props.
        return Inertia::render('AttendanceView', $props);
    }

    /**
     * Fetches a unique list of users who have logged in today.
     * @return \Illuminate\Support\Collection
     */
    private function getTodayList()
    {
        // fetches the list of users who logged in today
        return User::whereHas(
            'timeLogs',  
            fn($q) => $q->whereDate('date', today())
        )
        ->with([
            'user.employeeDetails.department:id,dept_name',
            'user.internDetails.department:id,dept_name',
        ])
        ->get()
        ->map(fn($user)=> [
            'id' => $user->id,
            'name' => $user->name,
            'department' => // Gracefully determine the department name through optional()
                optional(optional($user->employeeDetails)->department)->dept_name
                ?? optional(optional($user->internDetails)->department)->dept_name
                ?? 'No Department',
            'position' => $user->position,
            'date' => now()->format('Y-m-d'),
        ])
        ->values(); // Reset the array keys to ensure it's a clean JSON array
    }

    /**
     * Fetches department attendance for the last 30 days.
     * Returns a distinct list of departments and dates where at least one user logged in.
     * @return \Illuminate\Support\Collection
     */
    private function getDeptAttendance()
    {
        // This query is more complex, so using the Query Builder is more direct and efficient.
        return TimeLog::query()
            // Join through users
            ->join('users', 'time_logs.user_id', '=', 'users.id')

            // Role-specific tables for department linkage
            ->leftJoin('user_employees', 'users.id', '=', 'user_employees.user_id')
            ->leftJoin('user_interns', 'users.id', '=', 'user_interns.user_id')
            
            // Join to departments if either mapping exists
            ->join('departments', fn($join) =>
                $join->on('departments.id', '=', 'user_employees.department_id')
                    ->orOn('departments.id', '=', 'user_interns.department_id')
            )

            // Filter date range
            ->where('time_logs.date', '>=', today()->subDays(30))
            
            // Select the required columns and rename 'dept_name' to 'department'
            ->select('departments.id', 'departments.dept_name as department', 'time_logs.date')
            
            // Ensure we only get one entry per department per day
            ->distinct()
            
            // Order the results for a clean presentation on the frontend
            ->orderBy('time_logs.date', 'desc')
            ->orderBy('department', 'asc')
            ->get();
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
