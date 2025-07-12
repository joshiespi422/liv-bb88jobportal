<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Leave;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get the authenticated user and eager load their type and department details
        $user = $request->user()->load('userType', 'employeeDetails.department');
        
        $userType = $user->userType->type_name;
        $userDepartmentName = $user->employeeDetails?->department?->dept_name;

        // Eager load related data for efficiency.
        $leavesQuery = Leave::query()->with([
            'user:id,name', 
            'leaveType:id,name',
            'leaveCategory:id,name',
            'status:id,status_name'
        ]);

        // Determine if the current user has permission to view leaves by department.
        $canViewByDepartment = ($userType === 'super_admin') || 
                               ($userType === 'employee' && $userDepartmentName === 'Admin');

        // --- Authorization & Filtering Logic ---

        if ($canViewByDepartment) {
            // user is a 'super_admin' or an 'employee' in the 'Admin' department.
            $departmentId = $request->query('dept', session('current_department_id'));

            // If no department is specified, use the first department.
            if (!$departmentId || !Department::find($departmentId)) {
                $firstDepartment = Department::query()->orderBy('id')->first();
                $departmentId = $firstDepartment?->id;
            }

            // Store the currently viewed department ID in the session for persistence
            if ($departmentId) {
                session(['current_department_id' => $departmentId]);
            }

            // Filter the leaves to only include users from the selected department.
            if ($departmentId) {
                $leavesQuery->whereHas('user.employeeDetails', function ($query) use ($departmentId) {
                    $query->where('department_id', $departmentId);
                });
            } else {
                // no departments exist at all
                $leavesQuery->whereRaw('1 = 0');
            }

        } elseif ($userType === 'employee') {
            // regular employee (not in the 'Admin' department).
            $leavesQuery->where('user_id', $user->id);

        } else {
            // no leave access.
            $leavesQuery->whereRaw('1 = 0');
        }

        $leaves = $leavesQuery->latest()->get();

        // --- Prepare Props for the Vue Component ---
        
        $props = [
            'leaves' => $leaves,
        ];

        // provide the full department list and current department ID
        if ($canViewByDepartment) {
            $props['departments'] = Department::query()->orderBy('dept_name')->get(['id', 'dept_name']);
            $props['currentDepartmentId'] = $departmentId ? (int)$departmentId : null;
        }

        // Render the Inertia view and pass the props.
        return Inertia::render('LeaveView', $props);
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
