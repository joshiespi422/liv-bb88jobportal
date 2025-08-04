<?php

namespace App\Listeners;

use App\Events\LeaveRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Leave;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;

class SendNewLeaveNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LeaveRequested $event): void
    {
        $leave = $event->leave->load([
            'user.employeeDetails.department', 
            'leaveCategory'
        ]);
        $submitterId = $leave->user_id;
        $categoryName = strtolower($leave->leaveCategory->name);

        // 1. Get super admin IDs - 1 query
        $superAdminIds = User::whereHas('userType', fn(Builder $q) => 
            $q->where('type_name', 'super_admin')
        )->pluck('id');

        
        // 2. Get ALL employees in "Admin" department - 1 query
        $adminDeptUsers = User::whereHas('employeeDetails.department', fn(Builder $q) => 
            $q->where('dept_name', 'Admin')
        )->pluck('id');

        // Combine and deduplicate IDs - in-memory processing
        $recipientIds = collect()
            ->merge($superAdminIds)
            ->merge($adminDeptUsers)
            ->unique()
            ->reject($submitterId); // Exclude submitter

        if ($recipientIds->isEmpty()) return;

        // Prepare bulk notification insert
        $message = "{$leave->user->name} has requested a {$categoryName} leave";
        $now = now();

        $notifications = $recipientIds->map(fn($id) => [
            'user_id' => $id,
            'message' => $message,
            'notifiable_id' => $leave->id,
            'notifiable_type' => Leave::class,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        // Bulk insert - 1 query
        Notification::insert($notifications);
    }
}
