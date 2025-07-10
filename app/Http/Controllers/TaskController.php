<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Task;
use App\Models\UserType;
use App\Models\Status;
use App\Models\Accomplishment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

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
            'done',
            'revision'
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
                        $statuses['for approval'],
                        $statuses['revision']
                    ]);
                break;
                
            case 'active_tasks':
                $tasksQuery->whereIn('status_id', [
                    $statuses['in progress'],
                    $statuses['for approval'],
                    $statuses['revision']
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

    public function fetchAssignees(Request $request, Department $department): JsonResponse
    {
        $type = $request->query('type', 'employee'); // Default to 'employee'

        // Ensure the type is valid
        if (!in_array($type, ['employee', 'intern'])) {
            return response()->json(['error' => 'Invalid user type specified.'], 400);
        }

        $query = User::query()
            ->whereHas($type === 'employee' ? 'employeeDetails' : 'internDetails', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            })
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($query);
    }

    public function updateTask(Request $request, Task $task)
    {
        // 1. Authorization
        if (!$task->users->contains($request->user())) {
            abort(403, 'not authorized');
        }

        // 2. Validation
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'link' => 'nullable|url',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx,doc|max:5120', 
            'status' => 'required|in:in progress,for approval',
        ]);

        
        DB::transaction(function () use ($request, $task) {
            // Create accomplishment
            $accomplishment = Accomplishment::create([
                'user_id' =>$request->user()->id,
                'title' => $request->title,
                'description' => $request->description,
                'link' => $request->link,
                'attachment' => $request->file('attachment') ? 
                    $request->file('attachment')->store('accomplishment-files', 'public') : null
            ]);

            // Associate accomplishment with task
            $task->accomplishments()->attach($accomplishment->id);

            // Update task status only if it changed
            $newStatusName = $request->status;
            $currentStatusName = $task->status->status_name;
            
            if ($newStatusName !== $currentStatusName) {
                $status = Status::where('status_name', $newStatusName)->first();
                
                if ($status) {
                    $task->status_id = $status->id;
                    $task->save();
                }
            }
        });

        return back()->with('success', 'Task has been updated successfully!');
    }

    public function validateTask(Request $request, Task $task)
    {
        // Authorization
        $user = $request->user();
        $isLeader = $user->userType->type_name === 'employee' && 
                    $user->employeeDetails->hierarchy === 'Leader';
        
        if (!$isLeader && $user->userType->type_name !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        // Validation
        $request->validate([
            'status' => 'required|in:done,revision',
            'revise_reason' => 'required_if:status,revision|string|max:1000',
        ]);

        // Logic
        DB::transaction(function () use ($request, $task) {
            $newStatus = Status::where('status_name', $request->status)->firstOrFail();
            $task->status_id = $newStatus->id;

            // If status is 'revision', save the reason. Otherwise, clear it.
            if ($request->status === 'revision') {
                $task->revise_reason = $request->revise_reason;
            } else {
                $task->revise_reason = null; // Clear reason if marked as done
            }

            $task->save();
        });

        return back()->with('success', 'Task has been validated successfully!');
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
        // Validate request
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'collateral' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'project' => 'nullable|string',
            'assignees' => 'required|array|min:1',
            'assignees.*' => 'integer|exists:users,id',
            'deadline' => ['required','date','after_or_equal:today'],
            'priority' => 'required|in:high,medium,low',
            'type' => 'required|in:employee,intern'
        ]);

        // Get status and user type
        $status = Status::firstWhere('status_name', 'in progress');
        $userType = UserType::firstWhere('type_name', $validated['type']);

        // Create task
        $task = Task::create([
            ...$validated,
            'status_id' => $status->id,
            'user_type_id' => $userType->id
        ]);

        // Attach assignees
        $task->users()->attach($validated['assignees']);

        return back()->with('success', 'Task created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $task = Task::with([
                'users:id,name',
                'status:id,status_name',
                'accomplishments.user:id,name'
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
            'assignees' => $task->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name
            ])->toArray(),
            'accomplishments' => $task->accomplishments->sortByDesc('created_at')->map(fn ($accomplishment) => [
                'id' => $accomplishment->id,
                'title' => $accomplishment->title,
                'user_name' => $accomplishment->user->name,
            ])->values()->toArray(),
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
