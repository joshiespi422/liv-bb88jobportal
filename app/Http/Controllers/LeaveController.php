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
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
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
        $user = $request->user()->loadMissing('userType', 'employeeDetails.department');
        $activeTab = in_array($request->tab, ['regular', 'special']) ? $request->tab : 'regular';
        
        // Determine who can view leaves by department
        $canViewByDepartment = $this->canViewByDepartment($user);
        
        // Get the current department ID if the user has permission
        $departmentId = $canViewByDepartment ? $this->getCurrentDepartmentId($request) : null;
        
        // Build the query based on user permissions and filters
        $query = $this->getLeavesQuery($user, $activeTab, $canViewByDepartment, $departmentId);
        
        // Execute the query and format the results
        $leaves = $this->formatLeaves($query->latest()->get());
        
        // Prepare all props for the Inertia view
        $props = $this->preparePropsForView($leaves, $user, $activeTab, $canViewByDepartment, $departmentId);

        // Render the view
        return Inertia::render('LeaveView', $props);
    }

    /**
     * Check if the user has permissions to view leaves across departments.
     */
    private function canViewByDepartment($user): bool
    {
        $userType = $user->userType->type_name;
        $departmentName = $user->employeeDetails?->department?->dept_name;

        return $userType === 'super_admin' || ($userType === 'employee' && $departmentName === 'Admin');
    }

    /**
     * Get the current department ID from the request or session.
     */
    private function getCurrentDepartmentId(Request $request): ?int
    {
        // get the dept id from url or session, default to 1st, and store in session
        $departmentId = $request->dept ?? session('current_department_id', Department::orderBy('id')->first()?->id);
        session(['current_department_id' => $departmentId]);
   
        return $departmentId;
    }

    /**
     * Build the Eloquent query for fetching leaves.
     */
    private function getLeavesQuery($user, string $activeTab, bool $canViewByDepartment, ?int $departmentId): Builder
    {
        $leaveTypeName = ucfirst($activeTab);

        $query = Leave::with(['user:id,name,picture', 'status:id,status_name'])
            ->select('id', 'created_at', 'user_id', 'status_id')
            ->whereHas('leaveType', fn($q) => $q->where('name', $leaveTypeName));

        // Apply filters based on user role
        if ($canViewByDepartment) {
            if ($departmentId) {
                return $query->whereHas('user.employeeDetails', fn($q) => $q->where('department_id', $departmentId));
            }
            return $query->whereRaw('1 = 0'); // No departments exist, so return no results
        }

        if ($user->userType->type_name === 'employee') {
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('1 = 0'); // User has no leave access
    }

    /**
     * Format the collection of leaves for the view.
     */
    private function formatLeaves(Collection $leaves): Collection
    {
        return $leaves->map(function ($leave) {
            return [
                'id' => $leave->id,
                'created_at' => $leave->created_at,
                'status' => $leave->status->status_name,
                'user' => [
                    'name' => $leave->user->name,
                    'picture' => $leave->user->picture
                        ? Storage::url($leave->user->picture)
                        : Storage::url('profile-images/default.png'),
                ],
            ];
        });
    }

    /**
     * Prepare the final props array for the Inertia component.
     */
    private function preparePropsForView(Collection $leaves, $user, string $activeTab, bool $canViewByDepartment, ?int $departmentId): array
    {
        $props = [
            'leaves' => $leaves,
            'activeTab' => $activeTab,
        ];

        if ($canViewByDepartment) {
            $props['departments'] = Department::query()->orderBy('dept_name')->get(['id', 'dept_name']);
            $props['currentDepartmentId'] = $departmentId ? (int)$departmentId : null;
        }

        if ($user->userType->type_name !== 'super_admin') {
            $props['leaveTypes'] = LeaveType::query()->orderBy('name')->get(['id', 'name']);
        }

        return $props;
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
            'request_date' => 'nullable|date|date_format:Y-m-d|after_or_equal:today|before:2100-01-01',
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
