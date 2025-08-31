<?php

namespace App\Http\Controllers;

use App\Models\Accomplishment;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

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
        if ($userType === 'employee' && $user->employeeDetails->hierarchy === 'Leader' && $accomplishmentType === 'intern') {
            $defaultTab = 'all';
        } else {
            $defaultTab = in_array($userType, ['employee', 'intern']) 
                ? 'own' 
                : 'all';
        }
        $activeTab = in_array($request->tab, ['own', 'all']) 
            ? $request->tab 
            : $defaultTab;
        
        $accomplishmentsQuery = Accomplishment::with([
                'user:id,name,picture',
                'tasks:id,title'
        ]);

        // Apply tab-specific filters
        if ($activeTab === 'own' && $userType === $accomplishmentType) {
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
                'user' => [
                        'name' => $accomplishment->user->name,
                        'picture' => $accomplishment->user->picture 
                            ? Storage::url($accomplishment->user->picture)  // Generates full URL for stored image
                            : Storage::url('profile-images/default.png'),  // Fallback to default image
                    ],
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
     * Export specific accomplishments by their IDs.
     */
    public function export(Request $request)
    {
        // 1. Validate the incoming request to ensure 'ids' is an array
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:accomplishments,id', // Ensure all IDs exist
        ]);

        $accomplishmentIds = $validated['ids'];

        // 2. Fetch only the requested accomplishments with their relations
        $accomplishments = Accomplishment::with(['user:id,name', 'tasks:id,title'])
            ->whereIn('id', $accomplishmentIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($accomplishment) {
                // 3. Map to the desired JSON structure for the exporter
                return [
                    'user_name' => $accomplishment->user->name,
                    'project_name' => optional($accomplishment->tasks->first())->title,
                    'accomplish_title' => $accomplishment->title,
                    'date_report' => Carbon::parse($accomplishment->created_at)->format('M j, Y, g:i a'),
                    'description' => $accomplishment->description,
                    'link' => $accomplishment->link,
                    'attachment_url' => $accomplishment->attachment ? asset(Storage::url($accomplishment->attachment)) : null
                ];
            });

        return response()->json($accomplishments);
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
    public function update(Request $request, Accomplishment $accomplishment)
    {
        // 1. Authorization - User must own the accomplishment
        if ($accomplishment->user_id !== $request->user()->id) {
            abort(403, 'not authorized');
        }

        // 2. Validate edit timeframe (same day only)
        $createdDate = $accomplishment->created_at->format('Y-m-d');
        $today = Carbon::now()->format('Y-m-d');
        
        if ($createdDate !== $today) {
            abort(403, 'editable only on the same day');
        }

        // 3. Validate input
        $request->validate([
            'description' => 'required|string',
            'link' => 'nullable|url',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx,doc|max:5120',
        ]);

        // 4. Handle attachment update
        $attachmentPath = $accomplishment->attachment;
        
        if ($request->hasFile('attachment')) {
            // Delete old attachment if exists
            if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                Storage::disk('public')->delete($attachmentPath);
            }
            
            // Store new attachment
            $attachmentPath = $request->file('attachment')
                ->store('accomplishment-files', 'public');
        }

        // 5. Update accomplishment
        $accomplishment->update([
            'description' => $request->description,
            'link' => $request->link,
            'attachment' => $attachmentPath,
        ]);

        return back()->with('success', 'Accomplishment updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
