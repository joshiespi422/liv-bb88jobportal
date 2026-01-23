<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialRequest;
use App\Models\Department;
use App\Models\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Events\MaterialRequestCreated;
use App\Events\MaterialRequestSigned;
use App\Events\MaterialRequestValidated;

class MaterialRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('userType', 'employeeDetails.department');

        // Determine who can view material requests by department
        $canViewByDepartment = $this->canViewByDepartment($user); 

        // Get the current department ID if the user has permission
        $departmentId = $canViewByDepartment ? $this->getCurrentDepartmentId($request) : null;

        // Build the query based on user permissions and filters
        $query = $this->getMaterialRequestsQuery($user, $canViewByDepartment, $departmentId);

        // Execute the query and format the results
        $materialRequests = $this->formatMaterialRequests($query->latest()->get());

        // Prepare all props for the Inertia view
        $props = $this->preparePropsForView($materialRequests, $user, $canViewByDepartment, $departmentId);

        // Render the view
        return Inertia::render('MaterialRequestView', $props);
    }

    /**
     * Check if the user has permissions to view material requests across departments.
     */
    private function canViewByDepartment($user): bool
    {
        $userType = $user->userType->type_name;
        return $userType === 'super_admin';
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
     * Build the Eloquent query for fetching material requests.
     */
    private function getMaterialRequestsQuery($user, $canViewByDepartment, $departmentId): Builder
    {
        $query = MaterialRequest::with(['requester:id,name,picture', 'status:id,status_name'])
        ->select('id', 'name', 'created_at', 'requester_id', 'status_id');
    
        // Apply filters based on user role
        if ($canViewByDepartment) {
            if ($departmentId) {
                return $query->whereHas('requester.employeeDetails', fn($q) => $q->where('department_id', $departmentId));
            }
            return $query->whereRaw('1 = 0'); // No departments exist, so return no results
        }
    
        if ($user->userType->type_name === 'employee') {
            return $query->where('department_id', $user->employeeDetails?->department_id);
        }
    
        return $query->whereRaw('1 = 0'); // User has no material request access
    }

    /**
     * Format the collection of material requests for the view.
     */
    private function formatMaterialRequests(Collection $materialRequests): Collection
    {
        return $materialRequests->map(function ($materialRequest) {
            return [
                'id' => $materialRequest->id,
                'material' => $materialRequest->name,
                'created_at' => $materialRequest->created_at,
                'status' => $materialRequest->status->status_name,
                'requester' => [
                    'name' => $materialRequest->requester->name,
                    'picture' => $materialRequest->requester->picture
                        ? Storage::url($materialRequest->requester->picture)
                        : Storage::url('profile-images/default.png'),
                ],
            ];
        });
    }

    /**
     * Prepare the final props array for the Inertia component.
     */
    private function preparePropsForView(Collection $materialRequests, $user, bool $canViewByDepartment, ?int $departmentId): array
    {
        $props = [
            'materialRequests' => $materialRequests,
        ];

        if ($canViewByDepartment) {
            $props['departments'] = Department::query()->orderBy('dept_name')->get(['id', 'dept_name']);
            $props['currentDepartmentId'] = $departmentId ? (int)$departmentId : null;
        }

        return $props;
    }

    public function signMaterialRequest(MaterialRequest $materialRequest)
    {
        // Authorization
        $user = Auth::user();
        $isHead = $user->employeeDetails?->is_head;
        $isSuperAdmin = $user->userType->type_name === 'super_admin';
        if (!$isHead || $isSuperAdmin) { 
            abort(403, 'not authorized'); 
        }

        $materialRequest->loadMissing([
            'status:id,status_name',
        ]);

        if ($materialRequest->status->status_name !== 'pending') {
            abort(403, 'material request is not pending');
        }

        // Logic
        DB::transaction(function () use ($materialRequest, $user) {
            $forApprovalStatusId = Status::where('status_name', 'for approval')->value('id');
            $materialRequest->update([
                'status_id' => $forApprovalStatusId,
                'signer_id' => $user->id
            ]);

            // Dispatch event
            MaterialRequestSigned::dispatch($materialRequest);
        });
        
        return back()->with('success', 'Material request signed successfully!');
    }

    public function validateMaterialRequest(Request $request, MaterialRequest $materialRequest)
    {
        // Authorization
        $user = Auth::user();
        $isSuperAdmin = $user->userType->type_name === 'super_admin';
        if (!$isSuperAdmin) { 
            abort(403, 'not authorized'); 
        }

        $materialRequest->loadMissing([
            'status:id,status_name',
        ]);

        if ($materialRequest->status->status_name !== 'for approval') {
            abort(403, 'material request is not for approval');
        }

        // Validation
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'reject_reason' => 'nullable|required_if:status,rejected|string|max:1000',
        ]);

         //Logic
        DB::transaction(function () use ($request, $materialRequest) {
            $newStatus = Status::where('status_name', $request->status)->firstOrFail();
            $materialRequest->status_id = $newStatus->id;

            // If status is 'rejected', save the reason. Otherwise, clear it.
            if ($request->status === 'rejected') {
                $materialRequest->reject_reason = $request->reject_reason;
            } else {
                $materialRequest->reject_reason = null;
            }

            $materialRequest->save();

            MaterialRequestValidated::dispatch($materialRequest);
        });
        
        return back()->with('success', 'Material request validated successfully!');
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
        $isHead = $user->employeeDetails?->is_head;
        
        $statusId = $isHead 
            ? Status::where('status_name', 'for approval')->value('id') 
            : Status::where('status_name', 'pending')->value('id');

        if (!$isEmployee) {
            abort(403, 'not authorized');
        }

        // Validation
        $request->validate([
            'purpose' => 'required|string|max:1000',
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'quantity' => 'required|integer|min:1|max:99',
            'amount' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
            'date_needed' => 'required|date|after_or_equal:today',
        ]);

        //Logic
        DB::transaction(function () use ($request, $user, $isHead, $statusId) {
            // Create Material Request
            $materialRequest = MaterialRequest::create([
                'requester_id' => $user->id,
                'name' => $request->name,
                'quantity' => $request->quantity,
                'purpose' => $request->purpose,
                'date_needed' => $request->date_needed,
                'amount' => $request->amount,
                'description' => $request->description,
                'remarks' => $request->remarks,
                'status_id' => $statusId,
                'department_id' => $user->employeeDetails?->department_id,
                'signer_id' => $isHead ? $user->id : null,
            ]);

            // dispatch event
            MaterialRequestCreated::dispatch($materialRequest);
        });

        return back()->with('success', 'Material request submitted successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Eager load related data for efficiency
        $materialRequest = MaterialRequest::with([
            'requester:id,name', 
            'department:id,dept_name',
            'signer:id,name',
            'status:id,status_name'
        ])->findOrFail($id);

        return response()->json([
            'id' => $materialRequest->id,
            'material' => $materialRequest->name,
            'quantity' => $materialRequest->quantity,
            'purpose' => $materialRequest->purpose,
            'date_needed' => $materialRequest->date_needed,
            'amount' => $materialRequest->amount,
            'description' => $materialRequest->description,
            'remarks' => $materialRequest->remarks ?? 'N/A',
            'created_at' => $materialRequest->created_at,
            'dept_name' => $materialRequest->department->dept_name,
            'requester' => $materialRequest->requester->name,
            'signer' => optional($materialRequest->signer)->name ?? 'N/A',
            'status' => $materialRequest->status->status_name,
            'reject_reason' => $materialRequest->reject_reason
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
