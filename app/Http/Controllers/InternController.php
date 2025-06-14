<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\UserIntern;
use App\Models\UserType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class InternController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $currentDepartmentId = null;
        
        // For interns: always use their department
        if ($user->hasRole('intern')) {
            $currentDepartmentId = $user->internDetails->department_id ?? null;
        }
        // For super_admins: use session-stored or request-selected department
        elseif ($user->hasRole('super_admin')) {
            // Get from request or session
            $currentDepartmentId = $request->input('department_id', session('current_department_id'));
            
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
        $internsQuery = User::with([
                'internDetails:user_id,school,department_id',
                'internDetails.department:id,dept_name'
            ])
            ->whereHas('internDetails')
            ->select('id', 'name');

        // Apply department filter if needed
        if ($currentDepartmentId) {
            $internsQuery->whereHas('internDetails', function ($q) use ($currentDepartmentId) {
                $q->where('department_id', $currentDepartmentId);
            });
        }

        $internsList = $internsQuery
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'deptName' => $user->internDetails->department->dept_name ?? null,
                    'school' => $user->internDetails->school ?? null,
                ];
        });

        return Inertia::render('InternsView', [
            'interns' => $internsList,
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
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'school' => 'required|string|max:255',
            'password' => 'required|string|min:8',
        ]);

    
        DB::transaction(function () use ($request) {
            // Find intern user type
            $internType = UserType::where('type_name', 'intern')->firstOrFail();

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'user_type_id' => $internType->id,
                'position' => $request->position,
            ]);

            // Create intern details
            UserIntern::create([
                'user_id' => $user->id,
                'department_id' => $request->department_id,
                'school' => $request->school,
            ]);
        });
        
        return back()->with('success', 'Intern created successfully');
     
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $intern = User::with([
                'internDetails:user_id,school,department_id',
                'internDetails.department:id,dept_name'
            ])
            ->whereHas('internDetails')
            ->select('id', 'name', 'position', 'address', 'gender', 'bday')
            ->findOrFail($id);

        $internDetails = [
            'id' => $intern->id,
            'name' => $intern->name,
            'position' => $intern->position,
            'address' => $intern->address,
            'gender' => $intern->gender,
            'bday' => $intern->bday,
            'dept_name' => $intern->internDetails && $intern->internDetails->department
                            ? $intern->internDetails->department->dept_name
                            : null,
            'school' => $intern->internDetails
                            ? $intern->internDetails->school
                            : null,
        ];
        return response()->json($internDetails);
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
