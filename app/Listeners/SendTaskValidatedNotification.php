<?php

namespace App\Listeners;

use App\Events\TaskValidated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Notification;
use App\Models\Task;

class SendTaskValidatedNotification
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
    public function handle(TaskValidated $event): void
    {
        $task = $event->task;

        // Get the user IDs directly using the relationship's query builder
        $assigneeIds = $task->users()->pluck('users.id');

        if ($assigneeIds->isEmpty()) {
            return;
        }

        $message = "The task {$task->title} has been marked as {$event->status}.";
        $now = now();

        // Prepare an array of notification data
        $notifications = $assigneeIds->map(function ($assigneeId) use ($task, $message, $now) {
            return [
                'user_id' => $assigneeId,
                'message' => $message,
                'notifiable_id' => $task->id,
                'notifiable_type' => Task::class,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        // Use a single query to insert all notifications
        Notification::insert($notifications);
    }
}
