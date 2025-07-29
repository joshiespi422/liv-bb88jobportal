<?php

namespace App\Listeners;

use App\Events\AccomplishmentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
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
        $submitter = $accomplishment->user; // The user who created the accomplishment

        // 1. Get all super admins
        $superAdmins = User::whereHas('userType', function (Builder $query) {
            $query->where('type_name', 'super_admin');
        })->get();

        // 2. Get all employee leaders of the task's department
        $departmentLeaders = User::whereHas('employeeDetails', function (Builder $query) use ($task) {
            $query->where('department_id', $task->department_id)
                  ->where('hierarchy', 'Leader');
        })->get();

        // 3. Get all assignees of the task
        $taskAssignees = $task->users;

        // 4. Merge all collections and get unique users by ID
        $recipients = $superAdmins
            ->merge($departmentLeaders)
            ->merge($taskAssignees)
            ->unique('id');

        // 5. Exclude the user who submitted the accomplishment and create notifications
        $message = "New accomplishment {$accomplishment->title} added by: {$submitter->name}";

        $recipients
            ->reject(fn($user) => $user->id === $submitter->id) // Exclude the submitter
            ->each(function ($user) use ($accomplishment, $message) {
                // Use the polymorphic relation on the Accomplishment model
                $accomplishment->notifications()->create([
                    'user_id' => $user->id,
                    'message' => $message,
                ]);
            });
    }
}
