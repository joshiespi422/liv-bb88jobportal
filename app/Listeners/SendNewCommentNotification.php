<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use App\Models\Notification;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;

class SendNewCommentNotification
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
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment->load([
            'user', 
            'commentable'
        ]);
        $submitterId = $comment->user_id;

        // 1. Get super admin IDs - 1 query
        $superAdminIds = User::whereHas('userType', fn(Builder $q) => 
            $q->where('type_name', 'super_admin')
        )->pluck('id');

        // 2. Get type-specific recipients
        $typeSpecificRecipientIds = $this->getTypeSpecificRecipients($comment);

       // Combine and deduplicate IDs - in-memory processing
        $recipientIds = collect()
            ->merge($superAdminIds)
            ->merge($typeSpecificRecipientIds)
            ->unique()
            ->reject(fn($id) => $id === $submitterId); // Exclude submitter

        if ($recipientIds->isEmpty()) return;

        // Prepare notification message
        $message = $this->createNotificationMessage($comment);
        $now = now();

        $notifications = $recipientIds->map(fn($id) => [
            'user_id' => $id,
            'message' => $message,
            'notifiable_id' => $comment->id,
            'notifiable_type' => Comment::class,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        // Bulk insert - 1 query
        Notification::insert($notifications);
        
    }

    /**
     * Get type-specific recipients based on commentable type
     */
    protected function getTypeSpecificRecipients(Comment $comment): \Illuminate\Support\Collection
    {
        $methodName = 'getRecipientsFor' . class_basename($comment->commentable_type);
        
        if (method_exists($this, $methodName)) {
            return $this->{$methodName}($comment->commentable);
        }

        return collect();
    }

    /**
     * Get recipients for Task comments
     */
    protected function getRecipientsForTask($task): \Illuminate\Support\Collection
    {
        $recipients = collect();

        // 1. Get task assignees
        $recipients = $recipients->merge(
            $task->users()->pluck('id')
        );

        // 2. Get department leaders of the task's department
        if ($task->department_id) {
            $departmentLeaders = User::whereHas('employeeDetails', function (Builder $q) use ($task) {
                $q->where('department_id', $task->department_id)
                  ->where('hierarchy', 'Leader');
            })->pluck('id');
            
            $recipients = $recipients->merge($departmentLeaders);
        }

        return $recipients;
    }
    
    // Add future methods for other types:
    // protected function getRecipientsForProject($project) { ... }
    // protected function getRecipientsForAccomplishment($accomplishment) { ... }

    /**
     * Create notification message based    on commentable type
     */
    protected function createNotificationMessage(Comment $comment): string
    {
        $userName = $comment->user->name;
        $commentedOn = $this->getCommentedOnName($comment);

        return "{$userName} commented on {$commentedOn}";
    }

    /**
     * Get the name of the item being commented on
     */
    protected function getCommentedOnName(Comment $comment): string
    {
        switch ($comment->commentable_type) {
            case 'App\Models\Task':
                return $comment->commentable->title ?? 'a task';
                break;
            // Add cases for other types
            // case 'App\Models\Project':
            //     return $comment->commentable->name ?? 'a project';
            default:
                return 'an item';
        }
    }
}
