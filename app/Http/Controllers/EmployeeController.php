<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Department;
use App\Models\UserEmployee;
use App\Models\UserType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $selectedDepartmentId = null;
        
        // For employees, use their department
        if ($user->hasRole('employee')) {
            $user->load(['employeeDetails.department']);
            $selectedDepartmentId = $user->employeeDetails->department->id ?? null;
        } 
        // For super_admin, use request parameter or default to first department
        else if ($user->hasRole('super_admin')) {
            $selectedDepartmentId = $request->get('department_id') ?? Department::first()?->id;
        }

        $employeesQuery = User::with([
            'employeeDetails:user_id,hierarchy,department_id',
            'employeeDetails.department:id,dept_name'
        ])
        ->whereHas('employeeDetails');
        
        // Filter by department if selected
        if ($selectedDepartmentId) {
            $employeesQuery->whereHas('employeeDetails', function ($query) use ($selectedDepartmentId) {
                $query->where('department_id', $selectedDepartmentId);
            });
        }
        
        $employeesList = $employeesQuery->select('id', 'name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'dept_name' => $user->employeeDetails && $user->employeeDetails->department
                                    ? $user->employeeDetails->department->dept_name
                                    : null,
                    'hierarchy' => $user->employeeDetails
                                    ? $user->employeeDetails->hierarchy
                                    : null,
                ];
            });

        return Inertia::render('EmployeesView', [
            'employees' => $employeesList,
            'departments' => Department::all(['id', 'dept_name']),
            'selectedDepartmentId' => $selectedDepartmentId,
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
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'qr_code' => 'nullable|string|regex:/^\d{2}-[A-Z]\d{4}-\d{4}$/',
            'position' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'hierarchy' => 'required|in:Leader,Member',
            'password' => 'required|string|min:8',
        ]);

        try {
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
            return redirect()->back()->with('success', 'Employee created successfully');
        } catch (\Exception $e) {     
            Log::error('Employee creation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create employee. Please try again.');
        }
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
            ->select('id', 'name', 'position', 'address', 'gender', 'bday')
            ->findOrFail($id);

        $employeeDetails = [
            'id' => $employee->id,
            'name' => $employee->name,
            'position' => $employee->position,
            'address' => $employee->address,
            'gender' => $employee->gender,
            'bday' => $employee->bday,
            'dept_name' => $employee->employeeDetails && $employee->employeeDetails->department
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
