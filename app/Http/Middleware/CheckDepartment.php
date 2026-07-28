<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckDepartment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$departments): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Single round-trip: only pulls whichever relation actually applies
        $user->loadMissing([
            'userType',
            'employeeDetails.department',
            'internDetails.department',
        ]);

        // super_admin has no department concept — always passes,
        // mirrors the frontend departmentPermission() behavior
        if ($user->userType?->type_name === 'super_admin') {
            return $next($request);
        }

        $department = $user->getDepartment();

        if (!$department || !in_array($department->dept_name, $departments)) {
            return abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
