<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employeesList = User::with([
                'employeeDetails:user_id,hierarchy,department_id', // Select specific columns from employeeDetails relationship
                'employeeDetails.department:id,dept_name' // Select specific columns from department relationship
            ])
            ->whereHas('employeeDetails') // Only select users with employee details
            ->select('id', 'name') // Select specific columns from users table
            ->get()
            ->map(function ($user) {
                return [
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
            'employees' => $employeesList
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
        //
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
