<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;
use App\Models\Accomplishment;
use App\Models\Leave;
use App\Models\Project;
use App\Models\Comment;
use App\Models\MaterialRequest;
use App\Models\Overtime;
use App\Models\ChatMessage;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $notifications = $user->notifications()
            // ✨ Eager load the same relationships here.
            ->with([
                'notifiable' => function ($morphTo) {
                    $morphTo->morphWith([
                        Task::class => ['userType', 'status', 'users', 'department'],
                        Accomplishment::class => ['user.userType', 'tasks.department'],
                        Project::class => [],
                        Leave::class => ['leaveType', 'user.employeeDetails.department'],
                        Comment::class => ['commentable' => function ($morphTo) {
                            $morphTo->morphWith([
                                Task::class => ['userType', 'status', 'users', 'department'],
                                Accomplishment::class => ['user.userType', 'tasks.department'],
                                Project::class => [],
                            ]);
                        }],
                        MaterialRequest::class => ['department'],
                        Overtime::class => [],
                        ChatMessage::class => [],
                    ]);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(20)->onEachSide(1);

        return Inertia::render('NotificationView', [
            'notifications' => $notifications
        ]);
    }

    public function latest()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $notifications = $user->notifications()
            ->with([
                'notifiable' => function ($morphTo) {
                    $morphTo->morphWith([
                        Task::class => ['userType', 'status', 'users', 'department'],
                        Accomplishment::class => ['user.userType', 'tasks.department'],
                        Project::class => [],
                        Leave::class => ['leaveType', 'user.employeeDetails.department'],
                        Comment::class => ['commentable' => function ($morphTo) {
                            $morphTo->morphWith([
                                Task::class => ['userType', 'status', 'users', 'department'],
                                Accomplishment::class => ['user.userType', 'tasks.department'],
                                Project::class => [],
                            ]);
                        }],
                        MaterialRequest::class => ['department'],
                        Overtime::class => [],
                        ChatMessage::class => [],
                    ]);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $total = $user->notifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'total' => $total
        ]);
    }

    public function markAllAsRead()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $user->notifications()->where('read', false)->update(['read' => true]);
        
        return response()->json(['success' => true]);
    }

    public function markAsRead($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $notification = $user->notifications()->findOrFail($id);
        $notification->update(['read' => true]);
        
        return response()->json(['success' => true]);
    }

    public function destroyAll()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $user->notifications()->delete();
        
        // Redirect back with a success flash message.
        return back()->with('success', 'All notifications have been deleted!');
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
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();
        
        return back()->with('success', 'Notification has been deleted!');
    }
}
