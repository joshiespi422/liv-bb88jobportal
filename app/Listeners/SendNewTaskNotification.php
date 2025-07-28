<?php

namespace App\Listeners;

use App\Events\TaskCreated;
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
        // Get the task from the event
        $task = $event->task;

        // Loop through each assignee
        foreach ($task->users as $assignee) {
            // Create a notification for the assignee
            // The morphMany relationship 'notifications()' handles setting
            // the 'notifiable_id' and 'notifiable_type' automatically.
            $task->notifications()->create([
                'user_id' => $assignee->id,
                'message' => "A new task has been added to you: {$task->title}",
            ]);
        }
    }
}
