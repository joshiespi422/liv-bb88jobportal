<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Department;
use App\Models\UserEmployee;
use App\Models\UserType;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('userType', 'employeeDetails');

        // Determine current department
        $currentDepartmentId = $this->getCurrentDepartment($request, $user);
        // Determine active tab
        $activeTab = $this->getActiveTab($request);
        // Build query
        $employeesQuery = $this->getEmployeesQuery($currentDepartmentId, $activeTab);

        $employeesList = $employeesQuery
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'picture' => $user->picture,
                    'deptName' => $user->employeeDetails->department->dept_name ?? null,
                    'hierarchy' => $user->employeeDetails->hierarchy ?? null,
                    'status' => $user->status->status_name
                ];
        });

        return Inertia::render('EmployeesView', [
            'employees' => $employeesList,
            'departments' => Department::all(['id', 'dept_name']),
            'currentDepartmentId' => $currentDepartmentId ? (int)$currentDepartmentId : null,
            'activeTab' => $activeTab
        ]);
    }

    /**
     * Get the current department ID based on the user's role and request parameters.
     */
    private function getCurrentDepartment(Request $request, $user): ?int
    {
        $userType = $user->userType->type_name;

        switch (true) {
            // SUPER ADMIN: Can view any department
            case $userType === 'super_admin':
                // get the dept id from url or session, default to 1st, and store in session
                $departmentId = $request->dept ?? session('current_department_id', Department::orderBy('id')->first()?->id);
                session(['current_department_id' => $departmentId]);
                return $departmentId;
            default:
                return $user->employeeDetails?->department_id;
        }
    }

    /**
     * Get the active tab based on the user's role and request parameters.
     */
    private function getActiveTab(Request $request): string
    {
        // Define the default tab
        $defaultTab = 'active';

        // Return the requested tab if valid, otherwise return the default
        return in_array($request->tab, ['active', 'separated']) ? $request->tab : $defaultTab;
    }

    /**
     * Builds the Eloquent query for fetching employees with appropriate filters.
     */
    private function getEmployeesQuery(?int $departmentId, string $activeTab): Builder
    {
        $statuses = Status::whereIn('status_name', ['active', 'resigned', 'terminated'])->pluck('id', 'status_name');

        $query = User::with([
            'status:id,status_name',
            'employeeDetails:user_id,hierarchy,department_id',
            'employeeDetails.department:id,dept_name'
        ])
            ->whereHas('employeeDetails')
            ->select('id', 'status_id', 'name', 'picture');

        // Apply department filter if needed
        if ($departmentId) {
            $query->whereHas('employeeDetails', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $separatedStatuses = [$statuses['resigned'], $statuses['terminated']];

        // Apply active tab filter
        switch ($activeTab) {
            case 'active':
                return $query->whereNotIn('status_id', $separatedStatuses);
            case 'separated':
                return $query->whereIn('status_id', $separatedStatuses);
            default:
                return $query;
        }

        return $query;
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
        // Authorization - must be super_admin or employee leader
        $user = $request->user()->loadMissing('userType', 'employeeDetails');
        if ($user->userType->type_name !== 'super_admin'
             && $user->employeeDetails?->hierarchy !== 'Leader') {
            return abort(403, 'not authorized');
        }
        
        $request->validate([
            'email' => 'required|email|unique:users,email|max:255',
            'name' => 'required|string|max:255',
            'qr_code' => 'nullable|string|unique:users,qr_code|regex:/^\d{2}-[A-Z]\d{4}-\d{4}$/',
            'position' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'hierarchy' => 'required|in:Leader,Member',
            'password' => 'required|string|min:8',
        ]);


        DB::transaction(function () use ($request) {
            // Find employee user type, and active status
            $employeeType = UserType::where('type_name', 'employee')->firstOrFail();
            $activeStatus = Status::where('status_name', 'active')->firstOrFail();

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'user_type_id' => $employeeType->id,
                'status_id' => $activeStatus->id,
                'position' => $request->position,
                'qr_code' => $request->qr_code,
            ]);

            // Create employee details
            UserEmployee::create([
                'user_id' => $user->id,
                'department_id' => $request->department_id,
                'hierarchy' => $request->hierarchy,
            ]);
        });
        
        return back()->with('success', 'Employee created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $isSuperAdmin = $user && $user->userType->type_name === 'super_admin';

        $employee = User::with([
                'status:id,status_name',
                'employeeDetails:user_id,hierarchy,department_id,terminate_reason,current_salary',
                'employeeDetails.department:id,dept_name'
            ])
            ->whereHas('employeeDetails')
            ->select('id', 'status_id', 'name', 'email', 'position', 'qr_code', 'picture', 'address', 'gender', 'bday')
            ->findOrFail($id);

        $employeeDetails = [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'position' => $employee->position,
            'status' => $employee->status->status_name,
            'qr_code' => $employee->qr_code,
            'picture' => $employee->picture,
            'address' => $employee->address,
            'gender' => $employee->gender,
            'bday' => $employee->bday,
            'terminate_reason' => $employee->employeeDetails->terminate_reason,
            'deptName' => $employee->employeeDetails && $employee->employeeDetails->department
                            ? $employee->employeeDetails->department->dept_name
                            : null,
            'hierarchy' => $employee->employeeDetails
                            ? $employee->employeeDetails->hierarchy
                            : null,
            'current_salary' => ($isSuperAdmin && $employee->employeeDetails && $employee->employeeDetails->current_salary)
                                ? $employee->employeeDetails->current_salary
                                : null
        ];
        return response()->json($employeeDetails);
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
    public function update(Request $request, User $employee)
    {
        // Authorization - must be super_admin, and updating an employee
        $user = $request->user()->loadMissing('userType');
        $employee->loadMissing('userType', 'employeeDetails');
        if (!$user || $user->userType->type_name !== 'super_admin') {
            return abort(403, 'not authorized');
        } elseif ($employee->userType->type_name !== 'employee') {
            return abort(403, 'not employee');
        }

        // Validation
        $rules = [
            'status' => 'sometimes|required|in:active,resigned,terminated',
            'terminate_reason' => 'required_if:status,terminated|string|max:1000',
            'position' => 'sometimes|required|string|max:255',
            'qr_code' => 'sometimes|required|string|unique:users,qr_code,' . $employee->id,
            'current_salary' => 'sometimes|required|numeric|min:0',
        ];
        $request->validate($rules);

        // Logic
        DB::transaction(function () use ($request, $employee) {
            // 1. Update User table fields
            $employee->fill($request->only(['position', 'qr_code']));

            // 2. Handle Status logic
            if ($request->has('status')) {
                $newStatus = Status::where('status_name', $request->status)->firstOrFail();
                $employee->status_id = $newStatus->id;
                
                $employee->loadMissing('employeeDetails');
                $employee->employeeDetails->terminate_reason = ($request->status === 'terminated') 
                    ? $request->terminate_reason 
                    : null;
            }

            // 3. Update employeeDetails table fields
            if ($request->has('current_salary')) {
                $employee->loadMissing('employeeDetails');
                $employee->employeeDetails->current_salary = $request->current_salary;
            }

            $employee->save();
            if ($employee->relationLoaded('employeeDetails')) {
                $employee->employeeDetails->save();
            }
        });

        return back()->with('success', 'Employee updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
