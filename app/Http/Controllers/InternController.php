<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\UserIntern;
use App\Models\UserType;
use App\Models\Status;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class InternController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('userType', 'internDetails');
        
        // Determine current department
        $currentDepartmentId = $this->getCurrentDepartment($request, $user);
        // Determine active tab
        $activeTab = $this->getActiveTab($request);
        // Build query
        $internsQuery = $this->getInternsQuery($currentDepartmentId, $activeTab);

        $internsList = $internsQuery
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'picture' => $user->picture,
                    'deptName' => $user->internDetails->department->dept_name ?? null,
                    'school' => $user->internDetails->school ?? null,
                    'status' => $user->status->status_name
                ];
        });

        return Inertia::render('InternsView', [
            'interns' => $internsList,
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
                return $user->internDetails?->department_id;
        }
    }

    /**
     * Get the active tab based on the user's role and request parameters.
     */
    private function getActiveTab(Request $request): string
    {
        // Define the default tab
        $defaultTab = 'ongoing';

        // Return the requested tab if valid, otherwise return the default
        return in_array($request->tab, ['ongoing', 'completed']) ? $request->tab : $defaultTab;
    }

    /**
     * Builds the Eloquent query for fetching interns with appropriate filters.
     */
    private function getInternsQuery(?int $departmentId, string $activeTab): Builder
    {
        $statuses = Status::whereIn('status_name', ['ongoing', 'completed'])->pluck('id', 'status_name');

        $query = User::with([
            'status:id,status_name',
            'internDetails:user_id,school,department_id',
            'internDetails.department:id,dept_name'
        ])
            ->whereHas('internDetails')
            ->select('id', 'status_id', 'name', 'picture');

        // Apply department filter if needed
        if ($departmentId) {
            $query->whereHas('internDetails', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        // Apply active tab filter
        switch ($activeTab) {
            case 'ongoing':
                return $query->where('status_id', $statuses['ongoing']);
            case 'completed':
                return $query->where('status_id', $statuses['completed']);
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
            'position' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'school' => 'required|string|max:255',
            'password' => 'required|string|min:8',
        ]);

    
        DB::transaction(function () use ($request) {
            // Find intern user type, and ongoing status
            $internType = UserType::where('type_name', 'intern')->firstOrFail();
            $ongoingStatus = Status::where('status_name', 'ongoing')->firstOrFail();

            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'user_type_id' => $internType->id,
                'status_id' => $ongoingStatus->id,
                'position' => $request->position,
            ]);

            // Create intern details
            UserIntern::create([
                'user_id' => $user->id,
                'department_id' => $request->department_id,
                'school' => $request->school,
            ]);
        });
        
        return back()->with('success', 'Intern created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $intern = User::with([
                'status:id,status_name',
                'internDetails:user_id,school,department_id,created_at,completion_date',
                'internDetails.department:id,dept_name'
            ])
            ->whereHas('internDetails')
            ->select('id', 'status_id', 'name', 'email', 'position', 'picture', 'address', 'gender', 'bday')
            ->findOrFail($id);

        $internDetails = [
            'id' => $intern->id,
            'name' => $intern->name,
            'email' => $intern->email,
            'position' => $intern->position,
            'picture' => $intern->picture,
            'address' => $intern->address,
            'gender' => $intern->gender,
            'bday' => $intern->bday,
            'deptName' => $intern->internDetails && $intern->internDetails->department
                            ? $intern->internDetails->department->dept_name
                            : null,
            'school' => $intern->internDetails
                            ? $intern->internDetails->school
                            : null,
            'status' => $intern->status->status_name,
            'timeline' => [
                'created_at' => $intern->internDetails->created_at,
                'completion_date' => $intern->internDetails->completion_date
            ]
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
    public function update(Request $request, User $intern)
    {
        // Authorization - must be super_admin or employee leader, and updating an intern
        $user = $request->user()->loadMissing('userType', 'employeeDetails');
        $intern->loadMissing('userType', 'internDetails');
        if ($user->userType->type_name !== 'super_admin'
             && $user->employeeDetails?->hierarchy !== 'Leader') {
            return abort(403, 'not authorized');
        } elseif ($intern->userType->type_name !== 'intern') {
            return abort(403, 'not intern');
        }

        // Validation
        $request->validate([
            'status' => 'required|in:active,completed',
            'qr_code' => 'nullable|string|unique:users,qr_code|regex:/^\d{2}-[A-Z]\d{4}-\d{4}$/',
            'position' => 'nullable|required_if:status,active|string|max:255',
            'hierarchy' => 'nullable|required_if:status,active|in:Leader,Member',
        ]);

        // Logic
        DB::transaction(function () use ($request, $intern) {
            
            // promotion if status is active
            if ($request->status === 'active') {
                // change user type to employee, and status to active
                $employeeType = UserType::where('type_name', 'employee')->firstOrFail();
                $activeStatus = Status::where('status_name', 'active')->firstOrFail();

                // update user
                $intern->update([
                    'user_type_id' => $employeeType->id,
                    'status_id' => $activeStatus->id,
                    'position' => $request->position,
                    'qr_code' => $request->qr_code,
                ]);

                // cache department_id before deleting
                $departmentId = $intern->internDetails->department_id;
                // delete intern details
                $intern->internDetails()->delete();

                // create employee details with same department_id
                $intern->employeeDetails()->create([
                    'user_id' => $intern->id,
                    'department_id' => $departmentId,
                    'hierarchy' => $request->hierarchy,
                ]);
            } else {
                $newStatus = Status::where('status_name', $request->status)->firstOrFail();
                $today = now()->toDateString();
                // update user
                $intern->update([
                    'status_id' => $newStatus->id,
                ]);
                $intern->internDetails()->update([
                    'completion_date' => $today,
                ]);
            }
        });

        return back()->with('success', 'Intern updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
