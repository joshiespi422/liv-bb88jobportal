<?php

namespace App\Http\Controllers;

use App\Models\Project;
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
        $projects->transform(function ($project) {
            // Process assignees to remove the pivot object
            $project->assignees = $project->tasks->flatMap(function ($task) {
                return $task->users;
            })
            ->unique('id')
            ->each->makeHidden('id') // the 'id' attribute from each user model
            ->each->makeHidden('pivot') // the 'pivot' attribute from each user model
            ->values();
            
            // Clean up departments - return only department names array
            $departmentNames = [];
            foreach ($project->departments as $department) {
                $departmentNames[] = $department->dept_name;
            }
            $project->departments = $departmentNames;
            
            // Remove the raw tasks relationship to avoid redundant data in the prop
            unset($project->tasks);
            
            return $project;
        });

        return Inertia::render('ProjectView', [
            'projects' => $projects
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
