<?php

namespace App\Http\Controllers;

use App\Models\Accomplishment;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class AccomplishmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $userType = $user->userType->type_name;
        $currentDepartmentId = null;
        $accomplishmentType = null;

        // SUPER ADMIN: Handle type and department parameters
        if ($userType === 'super_admin') {
            // Validate and set accomplishment type
            $accomplishmentType = in_array($request->type, ['employee', 'intern']) 
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
            $accomplishmentType = in_array($request->type, ['employee', 'intern']) 
                ? $request->type 
                : 'employee';
                
            $currentDepartmentId = $user->employeeDetails->department_id;
        }
        // REGULAR EMPLOYEE & INTERN: No parameters
        else {
            $accomplishmentType = ($userType === 'intern') ? 'intern' : 'employee';
            
            $currentDepartmentId = ($userType === 'employee')
                ? $user->employeeDetails->department_id
                : $user->internDetails->department_id;
        }

        // Determine active tab
        $defaultTab = in_array($userType, ['employee', 'intern']) ? 'your_accomplishments' : 'all_accomplishments';
        $activeTab = in_array($request->tab, ['your_accomplishments', 'all_accomplishments']) 
            ? $request->tab 
            : $defaultTab;
        
        $accomplishmentsQuery = Accomplishment::with([
                'user:id,name',
                'tasks:id,title'
        ]);

        // Apply tab-specific filters
        if ($activeTab === 'your_accomplishments') {
            // Show only current user's accomplishments
            $accomplishmentsQuery->where('user_id', $user->id);
        } else {
            // "All Accomplishments" tab logic
            if ($accomplishmentType === 'employee') {
                $accomplishmentsQuery->whereHas('user.employeeDetails', function ($q) use ($currentDepartmentId) {
                    $q->where('department_id', $currentDepartmentId);
                });
            } elseif ($accomplishmentType === 'intern') {
                $accomplishmentsQuery->whereHas('user.internDetails', function ($q) use ($currentDepartmentId) {
                    $q->where('department_id', $currentDepartmentId);
                });
            }
        } 

        $accomplishments = $accomplishmentsQuery->orderBy('created_at', 'desc')->get()->map(function ($accomplishment) {
            return [
                'id' => $accomplishment->id,
                'title' => $accomplishment->title,
                'task_title' => $accomplishment->tasks->first()->title,
                'created_at' => $accomplishment->created_at,
                'user_name' => $accomplishment->user->name
            ];
        });

        return Inertia::render('AccomplishmentView', [
            'accomplishments' => $accomplishments,
            'departments' => ($userType === 'super_admin') 
                ? Department::all(['id', 'dept_name']) 
                : [],
            'currentDepartmentId' => $currentDepartmentId ? (int)$currentDepartmentId : null,
            'currentType' => $accomplishmentType,
            'activeTab' => $activeTab
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
      
        $accomplishment = Accomplishment::with([
                'user:id,name',
                'tasks:id,title'
            ])
            ->findOrFail($id);

        return response()->json([
            'id' => $accomplishment->id,
            'title' => $accomplishment->title,
            'description' => $accomplishment->description,
            'link' => $accomplishment->link,
            'attachment' => $accomplishment->attachment 
                ? [
                    'url' => Storage::url($accomplishment->attachment),
                    'name' => basename($accomplishment->attachment)
                ]
                : null,
            'created_at' => $accomplishment->created_at,
            'user_name' => $accomplishment->user->name,
            'task_title' => $accomplishment->tasks->first()->title, 
        ]);
        
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
