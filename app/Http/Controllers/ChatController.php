<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatMessage;
use App\Events\MessageSent;
use App\Models\User;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing(['userType', 'status', 'employeeDetails']);
        
        // Define Status and Type Constants for readability
        $isActiveEmployee = ($user->userType->type_name === 'employee' && $user->status->status_name === 'active');
        $isOngoingIntern = ($user->userType->type_name === 'intern' && $user->status->status_name === 'ongoing');
        $isSuperAdmin = ($user->userType->type_name === 'super_admin');
        $isLeader = ($user->employeeDetails && $user->employeeDetails->hierarchy === 'Leader');

        // 1. Permissions Logic
        $canAccessCore = $isSuperAdmin || ($isActiveEmployee && $isLeader);
        $canAccessEmployees = $isSuperAdmin || $isActiveEmployee;
        // Everyone (Super Admin, Active Employees, Ongoing Interns) can access Intern Group
        $canAccessInterns = $isSuperAdmin || $isActiveEmployee || $isOngoingIntern;

        // 2. Fetch Members (Reusable query parts)
        $members = [
            'core' => $canAccessCore ? User::whereHas('userType', fn($q) => $q->where('type_name', 'super_admin'))
                ->orWhere(function($query) {
                    $query->whereHas('userType', fn($q) => $q->where('type_name', 'employee'))
                          ->whereHas('status', fn($q) => $q->where('status_name', 'active'))
                          ->whereHas('employeeDetails', fn($q) => $q->where('hierarchy', 'Leader'));
                })->get() : [],

            'employees' => $canAccessEmployees ? User::whereHas('userType', fn($q) => $q->whereIn('type_name', ['super_admin', 'employee']))
                ->whereHas('status', fn($q) => $q->where('status_name', 'active'))
                ->get() : [],

            'interns' => $canAccessInterns ? User::where(function($query) {
                $query->whereHas('status', fn($q) => $q->whereIn('status_name', ['active', 'ongoing']));
            })->get() : [],
        ];

        return Inertia::render('ChatView', [
            'auth_permissions' => [
                'can_core' => $canAccessCore,
                'can_employees' => $canAccessEmployees,
                'can_interns' => $canAccessInterns,
            ],
            'group_members' => $members
        ]);
    }
}
