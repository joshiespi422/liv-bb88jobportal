<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Leave;
use App\Models\LeaveCategory;
use App\Models\LeaveType;
use App\Models\Status;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Events\LeaveRequested;
use App\Events\LeaveValidated;

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

        // Determine active tab from query parameter (default: 'regular')
        $tab = $request->query('tab', 'regular');
        $allowedTabs = ['regular', 'special'];
        $activeTab = in_array($tab, $allowedTabs) ? $tab : 'regular';
        // Map tab to leave type name
        $leaveTypeName = ucfirst($activeTab);

        // Eager load related data for efficiency.
        $leavesQuery = Leave::query()->with([
            'user:id,name,picture', 
            'status:id,status_name'
        ])->select('id', 'created_at', 'user_id', 'status_id'); 

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

        // Add leave type filter based on activeTab
        $leavesQuery->whereHas('leaveType', function ($query) use ($leaveTypeName) {
            $query->where('name', $leaveTypeName);
        });

        $leaves = $leavesQuery->latest()->get()->map(function ($leave) {
            return [
                'id' => $leave->id,
                'created_at' => $leave->created_at,
                'user' => [
                        'name' => $leave->user->name,
                        'picture' => $leave->user->picture 
                            ? Storage::url($leave->user->picture)  // Generates full URL for stored image
                            : Storage::url('profile-images/default.png'),  // Fallback to default image
                    ],
                'status' => $leave->status->status_name,
            ];
        });

        // --- Prepare Props for the Vue Component ---
        $props = [
            'leaves' => $leaves,
            'activeTab' => $activeTab,
        ];

        // provide the full department list and current department ID
        if ($canViewByDepartment) {
            $props['departments'] = Department::query()->orderBy('dept_name')->get(['id', 'dept_name']);
            $props['currentDepartmentId'] = $departmentId ? (int)$departmentId : null;
        }

        // provide the full leave type and category lists
        if ($userType !== 'super_admin') {
            $props['leaveTypes'] = LeaveType::query()->orderBy('name')->get(['id', 'name']);
        }

        // Render the Inertia view and pass the props.
        return Inertia::render('LeaveView', $props);
    }

    public function fetchCategories(int $leaveTypeId): JsonResponse
    {
        $query = LeaveCategory::query()
            ->where('leave_type_id', $leaveTypeId)
            ->select('id', 'name', 'days')
            ->get();
       
        return response()->json($query);
    }

    public function validateLeave(Request $request, Leave $leave)
    {
        // Get the authenticated user and eager load their type and department details
        $user = $request->user()->load('userType', 'employeeDetails.department');
        $userType = $user->userType->type_name;
        $userDeptName = $user->employeeDetails?->department?->dept_name;
       
        // Authorization
        if ($userType !== 'super_admin' && $userDeptName !== 'Admin') {
            abort(403, 'not authorized');
        } elseif ($leave->status->status_name !== 'pending') {
            abort(403, 'leave is not pending');
        }

        // Validation
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'reject_reason' => 'nullable|required_if:status,rejected|string|max:1000',
            'hard_copy' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        
        //Logic
        DB::transaction(function () use ($request, $leave) {
            $newStatus = Status::where('status_name', $request->status)->firstOrFail();
            $leave->status_id = $newStatus->id;

            // If status is 'rejected', save the reason. Otherwise, clear it.
            if ($request->status === 'rejected') {
                $leave->reject_reason = $request->reject_reason;
            } else {
                $leave->reject_reason = null; // Clear reason if marked as approved
            }

            // Upload hard copy if provided
            if ($request->hasFile('hard_copy')) {

                $hardCopyPath = $leave->hard_copy;

                // Delete old hard copy if it exists
                if ($hardCopyPath && Storage::disk('public')->exists($hardCopyPath)) {
                    Storage::disk('public')->delete($hardCopyPath);
                }

                // Store new hard copy
                $hardCopyPath = $request->file('hard_copy')->store('leave-hard-copies', 'public');
                $leave->hard_copy = $hardCopyPath;
            }

            $leave->save();

            // dispatch event
            LeaveValidated::dispatch($leave);
        });

        return back()->with('success', 'Leave has been validated successfully!');
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
        // Authorization
        $user = $request->user();
        $isEmployee = $user->userType->type_name === 'employee';
        
        if (!$isEmployee) {
            abort(403, 'not authorized');
        }

        // Validation
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'leave_category_id' => 'required|exists:leave_categories,id',
            'request_date' => 'nullable|date',
            'reason' => 'required|string|max:1000',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Get status
        $status = Status::firstWhere('status_name', 'pending');

        // Create leave
        $leave = Leave::create([
            'user_id' => $user->id,
            'leave_type_id' => $request->leave_type_id,
            'leave_category_id' => $request->leave_category_id,
            'status_id' => $status->id,
            'request_date' => $request->request_date,
            'reason' => $request->reason,
            'proof' => $request->file('proof') ? 
                $request->file('proof')->store('leave-proofs', 'public') : null
        ]);

        // dispatch event
        LeaveRequested::dispatch($leave);

        return back()->with('success', 'Leave request submitted successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Eager load related data for efficiency
        $leave = Leave::with([
            'user:id,name', 
            'user.employeeDetails.department:id,dept_name',
            'leaveType:id,name',
            'leaveCategory:id,name,days',
            'status:id,status_name'
        ])->findOrFail($id);
        
        // condtionally display the number of days or the actual date
        $requestDateDisplay = $leave->request_date;
        if ($leave->leaveType->name === 'Special') {
            $days = $leave->leaveCategory->days;
            $requestDateDisplay = $days . ' days';
        }

        return response()->json([
            'id' => $leave->id,
            'created_at' => $leave->created_at,
            'dept_name' => $leave->user->employeeDetails?->department?->dept_name,
            'name' => $leave->user->name,
            'leave_type' => $leave->leaveType->name,
            'category' => $leave->leaveCategory->name,
            'status' => $leave->status->status_name,
            'reason' => $leave->reason,
            'reject_reason' => $leave->reject_reason,
            'request_date' => $requestDateDisplay,
            'proof' => $leave->proof
                ? [
                    'url' => Storage::url($leave->proof),
                    'name' => basename($leave->proof)
                ] : null,
            'hard_copy' => $leave->hard_copy
                ? [
                    'url' => Storage::url($leave->hard_copy),
                    'name' => basename($leave->hard_copy)
                ] : null
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
