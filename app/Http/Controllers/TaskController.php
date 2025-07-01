<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Task;
use App\Models\UserType;
use App\Models\Status;
use App\Models\Accomplishment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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

        // Determine active tab
        $defaultTab = in_array($userType, ['employee', 'intern']) ? 'your_tasks' : 'active_tasks';
        $activeTab = in_array($request->tab, ['your_tasks', 'active_tasks', 'archived']) 
            ? $request->tab 
            : $defaultTab;

        // Get status IDs for filtering
        $statuses = Status::whereIn('status_name', [
            'in progress', 
            'for approval',
            'done'
        ])->pluck('id', 'status_name');

        // Get user type ID for query
        $userTypeId = UserType::where('type_name', $taskType)->value('id');

         // Build base query
        $tasksQuery = Task::with(['users:id,name,picture','status:id,status_name'])
            ->select('id', 'title', 'created_at', 'priority', 'status_id')
            ->where('department_id', $currentDepartmentId)
            ->where('user_type_id', $userTypeId);

        // Apply tab-specific filters
        switch ($activeTab) {
            case 'your_tasks':
                $tasksQuery->whereHas('users', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->whereIn('status_id', [
                        $statuses['in progress'],
                        $statuses['for approval']
                    ]);
                break;
                
            case 'active_tasks':
                $tasksQuery->whereIn('status_id', [
                    $statuses['in progress'],
                    $statuses['for approval']
                ]);
                break;
                
            case 'archived':
                $tasksQuery->where('status_id', $statuses['done']);
                break;
        }

        $tasks = $tasksQuery->get()->map(function ($task) {
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
            'activeTab' => $activeTab,
        ]);
    }

    public function updateTask(Request $request, Task $task)
    {
        // 1. Authorization
        if (!$task->users->contains($request->user())) {
            abort(403, 'not authorized to');
        }

        // 2. Validation
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'link' => 'nullable|url',
            'attachment' => 'nullable|file|mimes:jpg,png,pdf,docx|max:2048', 
            'status' => 'required|in:for approval,done',
        ]);

        
        DB::transaction(function () use ($request, $task) {
            // Create accomplishment
            $accomplishment = Accomplishment::create([
                'user_id' =>$request->user()->id,
                'title' => $request->accomplishment_title,
                'description' => $request->description,
                'link' => $request->link,
                'attachment' => $request->file('attachment') ? 
                    $request->file('attachment')->store('accomplishments', 'public') : null
            ]);

            // Associate accomplishment with task
            $task->accomplishments()->attach($accomplishment->id);

            // Update task status
            $status = Status::where('status_name', $request->status)->first();
            $task->status_id = $status->id;
            $task->save();
        });

        return back()->with('success', 'Task updated successfully!');
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
        $task = Task::with([
                'users:id,name,picture',
                'status:id,status_name'
            ])
            ->findOrFail($id);

        $taskDetails = [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'collateral' => $task->collateral,
            'created_at' => $task->created_at,
            'deadline' => $task->deadline,
            'priority' => $task->priority,
            'status' => $task->status->status_name,
            'assignees' => $task->users->pluck('name'),
        ];
        return response()->json($taskDetails);
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
