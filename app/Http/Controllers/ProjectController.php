<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Department;
use App\Models\ProjectIssue;
use App\Models\Status;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->load([
            'userType', 
            'employeeDetails.department', 
            'internDetails.department'
        ]);

        $projectsQuery = Project::select([
            'id',
            'title',
            'description',
            'created_at'
        ])
        // Eager load departments and the nested users through tasks
        ->with([
            'departments:id,dept_name',
            'tasks.users:id,name,picture' 
        ]);

        if (in_array($user->userType->type_name, ['employee', 'intern'])) {
            $departmentId = $user->userType->type_name === 'employee'
                ? $user->employeeDetails?->department_id
                : $user->internDetails?->department_id;

            if ($departmentId) {
                $projectsQuery->whereHas('departments', function ($query) use ($departmentId) {
                    $query->where('departments.id', $departmentId);
                });
            } else {
                $projectsQuery->whereRaw('1 = 0');
            }
        }
        
        $projects = $projectsQuery->get();

        // Transform the collection to create the desired data structure
        $formattedProjects = $projects->map(function ($project) {
            // Process assignees to remove the pivot object
            $assignees = $project->tasks->flatMap(function ($task) {
                return $task->users;
            })
            ->unique('id')
            ->map(function ($user) { // Using map to be explicit
                return [
                    'name' => $user->name,
                    'picture' => $user->picture
                        ? Storage::url($user->picture)  // Generates full URL for stored image
                        : Storage::url('profile-images/default.png'),  // Fallback to default image
                ];
            })
            ->values();

            // Return a new, explicitly structured array for the project
            return [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'created_at' => $project->created_at,
                'assignees' => $assignees,
                'departments' => $project->departments->pluck('dept_name'),
            ];
        });


        return Inertia::render('ProjectView', [
            'projects' => $formattedProjects,
            'departments' => ($user->userType->type_name === 'super_admin') 
                ? Department::all(['id', 'dept_name as name']) : [],
        ]);
    
    }

    public function showIssue(ProjectIssue $issue)
    {
        $issue->load([
            'project:id,title',
            'user:id,name',
            'status:id,status_name'
        ]);

        $issueDetails = [
            'id' => $issue->id,
            'title' => $issue->title,
            'description' => $issue->description,
            'solution' => $issue->solution,
            'created_at' => $issue->created_at,
            'status' => $issue->status->status_name,
            'project_title' => $issue->project->title,
            'user_name' => $issue->user->name
        ];

        return response()->json($issueDetails);
    }

    public function storeIssue(Request $request)
    {
        // Authorization
        $user = $request->user(); 
        
        if ($user->userType->type_name === 'super_admin') {
            abort(403, 'not authorized');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'project_id' => 'required|integer|exists:projects,id',
        ]);

        // Get user and status
        $userId = $user->id;
        $status = Status::firstWhere('status_name', 'pending');

        ProjectIssue::create([
            ...$validated,
            'user_id' => $userId,
            'status_id' => $status->id
        ]);

        return back()->with('success', 'Issue created successfully!');
    }

    public function resolveIssue(Request $request, ProjectIssue $issue)
    {
        // Authorization
        $user = $request->user(); 
        
        if ($user->userType->type_name !== 'super_admin') {
            abort(403, 'not authorized');
        } elseif ($issue->status->status_name !== 'pending') {
            abort(403, 'issue is not pending');
        }

        $validated = $request->validate([
            'solution' => 'required|string',
        ]);

        // Get status
        $status = Status::firstWhere('status_name', 'resolved');

        $issue->update([
            ...$validated,
            'status_id' => $status->id
        ]);

        return back()->with('success', 'Issue resolved successfully!');
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
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'client' => 'required|string',
            'deadline' => ['required','date','after_or_equal:today'],
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id'
        ]);
       
        $project = Project::create($validated);

        $project->departments()->sync($validated['department_ids']);
        
        return back()->with('success', 'Project created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $project = Project::with([
                'tasks:id,title',
                'departments:id,dept_name',
                'tasks.users:id,picture',
                'projectIssues:id,title,project_id,user_id',
                'projectIssues.user:id,name'
            ])
            ->findOrFail($id);

        $projectDetails = [
            'id' => $project->id,
            'title' => $project->title,
            'description' => $project->description,
            'client' => $project->client,
            'created_at' => $project->created_at,
            'deadline' => $project->deadline,
            'tasks' => $project->tasks->sortBy('created_at')->map(fn ($task) => [
                'id' => $task->id,
                'title' => $task->title,
                'assignees' => $task->users->map(fn ($user) => [
                    'id' => $user->id,
                    'picture' => $user->picture
                        ? Storage::url($user->picture)
                        : Storage::url('profile-images/default.png'),
                ])
            ])->values()->toArray(),
            'departments' => $project->departments->pluck('dept_name'),
            'issues' => $project->projectIssues->sortBy('created_at')->map(fn ($issue) => [
                'id' => $issue->id,
                'title' => $issue->title,
                'user_name' => $issue->user->name
            ])->values()->toArray()
        ];
       
        return response()->json($projectDetails);

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
