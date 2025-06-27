<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Task;
use App\Models\UserType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $userType = $user->userType->type_name;
        $currentDepartmentId = null;
        $taskType = null;

        // SUPER ADMIN: Handle type and department parameters
        if ($userType === 'super_admin') {
            // Validate and set task type
            $taskType = in_array($request->type, ['employee', 'intern']) 
                ? $request->type 
                : 'employee';

            // Get or set department from session
            $currentDepartmentId = $request->dept ?? session('current_department_id');
            
            if (!$currentDepartmentId || !Department::find($currentDepartmentId)) {
                $firstDept = Department::orderBy('id')->first();
                $currentDepartmentId = $firstDept->id ?? null;
            }
            
            session(['current_department_id' => $currentDepartmentId]);
        }
        // EMPLOYEE LEADER: Handle type parameter only
        elseif ($userType === 'employee' && $user->employeeDetails->hierarchy === 'Leader') {
            $taskType = in_array($request->type, ['employee', 'intern']) 
                ? $request->type 
                : 'employee';
                
            $currentDepartmentId = $user->employeeDetails->department_id;
        }
        // REGULAR EMPLOYEE & INTERN: No parameters
        else {
            $taskType = ($userType === 'intern') ? 'intern' : 'employee';
            
            $currentDepartmentId = ($userType === 'employee')
                ? $user->employeeDetails->department_id
                : $user->internDetails->department_id;
        }

        // Get user type ID for query
        $userTypeId = UserType::where('type_name', $taskType)->value('id');

        // Fetch tasks
        $tasks = Task::with(['users:id,name,picture','status:id,status_name'])
            ->select('id', 'title', 'created_at', 'priority', 'status_id')
            ->where('department_id', $currentDepartmentId)
            ->where('user_type_id', $userTypeId)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'created_at' => $task->created_at,
                    'priority' => $task->priority,
                    'status' => $task->status->status_name,
                    'assignees' => $task->users->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'picture' => $user->picture
                                ? Storage::url($user->picture)  // Generates full URL for stored image
                                : Storage::url('profile-images/default.png'),  // Fallback to default image
                        ];
                    })->toArray(),
                ];
            });

        return Inertia::render('TaskView', [
            'tasks' => $tasks,
            'departments' => ($userType === 'super_admin') 
                ? Department::all(['id', 'dept_name']) 
                : [],
            'currentDepartmentId' => (int)$currentDepartmentId,
            'currentType' => $taskType,
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
