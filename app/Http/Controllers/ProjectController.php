<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Department;
use App\Models\ProjectIssue;
use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Events\ProjectCreated;
use App\Events\ProjectIssueCreated;
use App\Events\ProjectIssueResolved;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('userType', 'employeeDetails', 'internDetails');

        // Build the query based on user permissions
        $query = $this->getProjectsQuery($user);

        // Execute the query and format the results
        $projects = $this->formatProjects($query->get());

        // Render the view with the projects and necessary props
        return Inertia::render('ProjectView', [
            'projects' => $projects,
            'departments' => $user->userType->type_name === 'super_admin'
                ? Department::all(['id', 'dept_name as name'])
                : [],
        ]);
    
    }

    /**
     * Build the Eloquent query for fetching projects, applying user-specific filters.
     */
    private function getProjectsQuery($user): Builder
    {
        $query = Project::with(['departments:id,dept_name', 'tasks.users:id,name,picture'])
            ->select('id', 'title', 'description', 'created_at');

        // If the user is not a super_admin, filter projects by their department
        if (in_array($user->userType->type_name, ['employee', 'intern'])) {
            $departmentId = $user->userType->type_name === 'employee'
                ? $user->employeeDetails?->department_id
                : $user->internDetails?->department_id;

            if ($departmentId) {
                return $query->whereHas('departments', fn($q) => $q->where('departments.id', $departmentId));
            }
            // If user has no department, they see no projects
            return $query->whereRaw('1 = 0');
        }

        // super_admin sees all projects
        return $query;
    }

    /**
     * Format the collection of projects for the view.
     */
    private function formatProjects(Collection $projects): Collection
    {
        return $projects->map(function ($project) {
            // Get a unique list of all users assigned to any task within the project
            $assignees = $project->tasks->flatMap(fn($task) => $task->users)
                ->unique('id')
                ->map(fn($assignee) => [
                    'id' => $assignee->id,
                    'name' => $assignee->name,
                    'picture' => $assignee->picture,
                ])
                ->values(); // Reset array keys

            return [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'created_at' => $project->created_at,
                'departments' => $project->departments->pluck('dept_name'),
                'assignees' => $assignees,
            ];
        });
    }

    public function showIssue(ProjectIssue $issue)
    {
        $issue->loadMissing([
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
            'description' => 'required|string|max:1000',
            'project_id' => 'required|integer|exists:projects,id',
        ]);

        // Get user and status
        $userId = $user->id;
        $status = Status::firstWhere('status_name', 'pending');

        $issue = ProjectIssue::create([
            ...$validated,
            'user_id' => $userId,
            'status_id' => $status->id
        ]);
        
        // dispatch event
        ProjectIssueCreated::dispatch($issue);

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
            'solution' => 'required|string|max:1000',
        ]);

        // Get status
        $status = Status::firstWhere('status_name', 'resolved');

        $issue->update([
            ...$validated,
            'status_id' => $status->id
        ]);

        // dispatch event
        ProjectIssueResolved::dispatch($issue);

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
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'client' => 'required|string|max:255',
            'deadline' => ['required','date','date_format:Y-m-d','after_or_equal:today','before:2100-01-01'],
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id'
        ]);
       
        $project = Project::create($validated);

        $project->departments()->sync($validated['department_ids']);

        // dispatch event
        ProjectCreated::dispatch($project, $validated['department_ids']);
        
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
                    'picture' => $user->picture,
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
