<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatMessage;
use App\Events\MessageSent;
use App\Events\ChatMessageCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $permissions = $this->getUserPermissions($user);

        // Determine the fallback default based on access
        $defaultTab = $permissions['core'] ? 'core'
            : ($permissions['employees'] ? 'employees' : 'interns');

        // Priority: query param → session → computed default
        $requestedTab = $request->query('group');
        $activeGroup  = (
            $requestedTab &&
            isset($permissions[$requestedTab]) &&
            $permissions[$requestedTab]
        )
            ? $requestedTab
            : $request->session()->get('group', $defaultTab);

        if (!$permissions[$activeGroup]) {
            $activeGroup = $defaultTab;
        }

        // Persist to session
        $request->session()->put('group', $activeGroup);

        // Load only 20 most recent messages for the active group
        $rawMessages = ChatMessage::with('user')
            ->where('group', $activeGroup)
            ->latest()
            ->take(20)
            ->get()
            ->reverse()
            ->values();
        
        $initialMessages = $rawMessages->map(fn($m) => [
            'id'          => $m->id,
            'message'     => $m->message,
            'group'       => $m->group,
            'created_at'  => $m->created_at->toDateTimeString(),
            'user'        => [
                'id'      => $m->user->id,
                'name'    => $m->user->name,
                'picture' => $m->user->picture,
            ],
        ]);

        // 2. Fetch Members (Reusable query parts)
        $members = [
            'core' => $permissions['core'] ? User::whereHas('userType', fn($q) => $q->where('type_name', 'super_admin'))
                ->orWhere(function($query) {
                    $query->whereHas('userType', fn($q) => $q->where('type_name', 'employee'))
                          ->whereHas('status', fn($q) => $q->where('status_name', 'active'))
                          ->whereHas('employeeDetails', fn($q) => $q->where('hierarchy', 'Leader'));
                })->get(['id', 'name', 'picture', 'position']) : [],

            'employees' => $permissions['employees'] ? User::whereHas('userType', fn($q) => $q->whereIn('type_name', ['super_admin', 'employee']))
                ->whereHas('status', fn($q) => $q->where('status_name', 'active'))
                ->get(['id', 'name', 'picture', 'position']) : [],

            'interns' => $permissions['interns'] ? User::where(function($query) {
                $query->whereHas('status', fn($q) => $q->whereIn('status_name', ['active', 'ongoing']));
            })->get(['id', 'name', 'picture', 'position']) : [],
        ];

        return Inertia::render('ChatView', [
            'auth_permissions' => [
                'can_core'      => $permissions['core'],
                'can_employees' => $permissions['employees'],
                'can_interns'   => $permissions['interns'],
            ],
            'group_members' => $members,
            'initial_messages' => $initialMessages,
            'active_group'    => $activeGroup,
            'current_user'    => [
                'id'      => $user->id,
                'name'    => $user->name,
                'picture' => $user->picture,
            ],
            'has_more' => ChatMessage::where('group', $activeGroup)->count() > 20,
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
            abort(403, 'not authorized');
        }

        DB::transaction(function () use ($request, $group, $validated) {
            // 1. Save message
            $chatMessage = ChatMessage::create([
                'user_id' => $request->user()->id,
                'group' => $group,
                'message' => $validated['message'],
            ]);

            // 2. Dispatch internal event to handle notifications
            ChatMessageCreated::dispatch($chatMessage, $group);

            // 3. Trigger WebSocket Broadcast safely after DB commits
            DB::afterCommit(function () use ($chatMessage, $group) {
                broadcast(new MessageSent(
                    $chatMessage->loadMissing('user'),
                    $group
                ))->toOthers();
            });
        });

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

    public function loadMore(Request $request)
    {
        $request->validate([
            'group'     => 'required|in:core,employees,interns',
            'before_id' => 'required|integer|min:1',
        ]);

        $permissions = $this->getUserPermissions($request->user());
        $group       = $request->group;

        if (!$permissions[$group]) {
            abort(403);
        }

        $messages = ChatMessage::with('user')
            ->where('group', $group)
            ->where('id', '<', $request->before_id)
            ->latest()
            ->take(20)
            ->get()
            ->reverse()
            ->values()
            ->map(fn($m) => [
                'id'         => $m->id,
                'message'    => $m->message,
                'group'      => $m->group,
                'created_at' => $m->created_at->toDateTimeString(),
                'user'       => [
                    'id'      => $m->user->id,
                    'name'    => $m->user->name,
                    'picture' => $m->user->picture,
                ],
            ]);

        return response()->json([
            'messages' => $messages,
            'has_more' => ChatMessage::where('group', $group)
                ->where('id', '<', $request->before_id)
                ->count() > 20,
        ]);
    }
}
