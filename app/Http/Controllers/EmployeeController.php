<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Department;
use App\Models\UserEmployee;
use App\Models\UserType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $currentDepartmentId = null;
        
        // For employees: always use their department
        if ($user->hasRole('employee')) {
            $currentDepartmentId = $user->employeeDetails->department_id ?? null;
        }
        // For super_admins: use session-stored or request-selected department
        elseif ($user->hasRole('super_admin')) {
            // Get from request or session
            $currentDepartmentId = $request->input('dept', session('current_department_id'));
            
            // Default to first department if none set
            if (!$currentDepartmentId) {
                $firstDept = Department::orderBy('id')->first();
                $currentDepartmentId = $firstDept->id ?? null;
            }
            
            // Store in session for persistence
            if ($currentDepartmentId) {
                session(['current_department_id' => $currentDepartmentId]);
            }
        }

        // Build query
        $employeesQuery = User::with([
                'employeeDetails:user_id,hierarchy,department_id',
                'employeeDetails.department:id,dept_name'
            ])
            ->whereHas('employeeDetails')
            ->select('id', 'name');

        // Apply department filter if needed
        if ($currentDepartmentId) {
            $employeesQuery->whereHas('employeeDetails', function ($q) use ($currentDepartmentId) {
                $q->where('department_id', $currentDepartmentId);
            });
        }

        $employeesList = $employeesQuery
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'deptName' => $user->employeeDetails->department->dept_name ?? null,
                    'hierarchy' => $user->employeeDetails->hierarchy ?? null,
                ];
        });

        return Inertia::render('EmployeesView', [
            'employees' => $employeesList,
            'departments' => Department::all(['id', 'dept_name']),
            'currentDepartmentId' => $currentDepartmentId ? (int)$currentDepartmentId : null,
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
        $request->validate([
            'email' => 'required|email|unique:users,email|max:255',
            'name' => 'required|string|max:255',
            'qr_code' => 'nullable|string|regex:/^\d{2}-[A-Z]\d{4}-\d{4}$/',
            'position' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'hierarchy' => 'required|in:Leader,Member',
            'password' => 'required|string|min:8',
        ]);


        DB::transaction(function () use ($request) {
            // Find employee user type
            $employeeType = UserType::where('type_name', 'employee')->firstOrFail();

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'user_type_id' => $employeeType->id,
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
        
        return back()->with('success', 'Employee created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $employee = User::with([
                'employeeDetails:user_id,hierarchy,department_id',
                'employeeDetails.department:id,dept_name'
            ])
            ->whereHas('employeeDetails')
            ->select('id', 'name', 'email', 'position', 'picture', 'address', 'gender', 'bday')
            ->findOrFail($id);

        $employeeDetails = [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'position' => $employee->position,
            'picture' => $employee->picture
                ? Storage::url($employee->picture)  // Generates full URL for stored image
                : Storage::url('profile-images/default.png'),  // Fallback to default image
            'address' => $employee->address,
            'gender' => $employee->gender,
            'bday' => $employee->bday,
            'deptName' => $employee->employeeDetails && $employee->employeeDetails->department
                            ? $employee->employeeDetails->department->dept_name
                            : null,
            'hierarchy' => $employee->employeeDetails
                            ? $employee->employeeDetails->hierarchy
                            : null,
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
