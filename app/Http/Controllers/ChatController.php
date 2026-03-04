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
        $permissions = $this->getUserPermissions($request->user());

        // 2. Fetch Members (Reusable query parts)
        $members = [
            'core' => $permissions['core'] ? User::whereHas('userType', fn($q) => $q->where('type_name', 'super_admin'))
                ->orWhere(function($query) {
                    $query->whereHas('userType', fn($q) => $q->where('type_name', 'employee'))
                          ->whereHas('status', fn($q) => $q->where('status_name', 'active'))
                          ->whereHas('employeeDetails', fn($q) => $q->where('hierarchy', 'Leader'));
                })->get() : [],

            'employees' => $permissions['employees'] ? User::whereHas('userType', fn($q) => $q->whereIn('type_name', ['super_admin', 'employee']))
                ->whereHas('status', fn($q) => $q->where('status_name', 'active'))
                ->get() : [],

            'interns' => $permissions['interns'] ? User::where(function($query) {
                $query->whereHas('status', fn($q) => $q->whereIn('status_name', ['active', 'ongoing']));
            })->get() : [],
        ];

        return Inertia::render('ChatView', [
            'auth_permissions' => [
                'can_core'      => $permissions['core'],
                'can_employees' => $permissions['employees'],
                'can_interns'   => $permissions['interns'],
            ],
            'group_members' => $members
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'group' => 'required|in:core,employees,interns',
            'message' => 'required|string|max:5000',
        ]);

        $permissions = $this->getUserPermissions($request->user());
        $group = $validated['group'];

        if (!$permissions[$group]) {
            abort(403, 'Unauthorized group access.');
        }

        // 💾 Save message
        $message = ChatMessage::create([
            'user_id' => $request->user()->id,
            'group' => $group,
            'message' => $validated['message'],
        ]);

        // Broadcast to VPS Reverb
        broadcast(new MessageSent(
            $message->loadMissing('user'),
            $group
        ))->toOthers();

        return back()->with('success', 'Message sent successfully!');
    }

    private function getUserPermissions($user)
    {
        $user->loadMissing(['userType', 'status', 'employeeDetails']);

        $typeName = $user->userType->type_name ?? '';
        $statusName = $user->status->status_name ?? '';
        $hierarchy = $user->employeeDetails->hierarchy ?? '';

        $isActiveEmployee = ($typeName === 'employee' && $statusName === 'active');
        $isOngoingIntern = ($typeName === 'intern' && $statusName === 'ongoing');
        $isSuperAdmin = ($typeName === 'super_admin');
        $isLeader = ($hierarchy === 'Leader');

        return [
            'core'      => $isSuperAdmin || ($isActiveEmployee && $isLeader),
            'employees' => $isSuperAdmin || $isActiveEmployee,
            'interns'   => $isSuperAdmin || $isActiveEmployee || $isOngoingIntern,
        ];
    }
}
