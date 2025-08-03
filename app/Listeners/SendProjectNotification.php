<?php

namespace App\Listeners;

use App\Events\ProjectCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Notification;
use App\Models\UserEmployee;
use App\Models\UserIntern;
use App\Models\Project;

class SendProjectNotification
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
    public function handle(ProjectCreated $event): void
    {
        $departmentIds = $event->departmentIds;
        
        // Get all user IDs from employees and interns in these departments
        $employeeUserIds = UserEmployee::whereIn('department_id', $departmentIds)
            ->pluck('user_id');
            
        $internUserIds = UserIntern::whereIn('department_id', $departmentIds)
            ->pluck('user_id');

        $userIds = $employeeUserIds->merge($internUserIds)->unique();

        if ($userIds->isEmpty()) {
            return;
        }

        $message = "A new project {$event->project->title} has been assigned to your department.";
        $now = now();

        $notifications = $userIds->map(function ($userId) use ($event, $message, $now) {
            return [
                'user_id' => $userId,
                'message' => $message,
                'notifiable_id' => $event->project->id,
                'notifiable_type' => Project::class,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        Notification::insert($notifications);
    }
}
