<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Department;
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
                'tasks.users:id,picture'
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
