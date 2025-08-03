<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use App\Models\Task;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNewTaskNotification
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
    public function handle(TaskCreated $event): void
    {
        $assignees = $event->assignees;

        $message = "A new task has been added to you: {$event->task->title}";
        $now = now();

        $notifications = collect($assignees)->map(function ($assignee) use ($event, $message, $now) {
            return [
                'user_id' => $assignee,
                'message' => $message,
                'notifiable_id' => $event->task->id,
                'notifiable_type' => Task::class,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->toArray();

        Notification::insert($notifications);
    }
}
