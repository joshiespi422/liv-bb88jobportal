<?php

namespace App\Listeners;

use App\Events\AccomplishmentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use App\Models\Notification;
use App\Models\Accomplishment;
use Illuminate\Database\Eloquent\Builder;

class SendAccomplishmentNotification
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
    public function handle(AccomplishmentCreated $event): void
    {
        $accomplishment = $event->accomplishment;
        $task = $event->task;
        $submitterId = $accomplishment->user_id;

        // 1. Get super admin IDs - 1 query
        $superAdminIds = User::whereHas('userType', fn(Builder $q) => 
            $q->where('type_name', 'super_admin')
        )->pluck('id');

        // 2. Get department leader IDs - 1 query
        $departmentLeaderIds = User::whereHas('employeeDetails', fn(Builder $q) => $q->where('department_id', $task->department_id)
        ->where('hierarchy', 'Leader'))->pluck('id');

        // 3. Get task assignee IDs - 1 query (if not already loaded)
        $taskAssigneeIds = $task->users()->pluck('users.id');

        // Combine and deduplicate IDs - in-memory processing
        $recipientIds = collect()
            ->merge($superAdminIds)
            ->merge($departmentLeaderIds)
            ->merge($taskAssigneeIds)
            ->unique()
            ->reject(fn($id) => $id === $submitterId); // Exclude submitter

        if ($recipientIds->isEmpty()) {
            return;
        }

        // Prepare bulk notification insert
        $message = "New accomplishment {$accomplishment->title} added by: {$accomplishment->user->name}";
        $now = now();
        $notifications = $recipientIds->map(fn($id) => [
            'user_id' => $id,
            'message' => $message,
            'notifiable_id' => $accomplishment->id,
            'notifiable_type' => Accomplishment::class,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        // Bulk insert - 1 query
        Notification::insert($notifications);
    }
}
