<?php

namespace App\Listeners;

use App\Events\ProjectIssueCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use App\Models\Notification;
use App\Models\ProjectIssue;
use Illuminate\Database\Eloquent\Builder;

class SendNewProjectIssueNotification
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
    public function handle(ProjectIssueCreated $event): void
    {
        $issue = $event->issue->load('user');

        // 1. Get super admin IDs - 1 query
        $superAdminIds = User::whereHas('userType', fn(Builder $q) => 
            $q->where('type_name', 'super_admin')
        )->pluck('id');

        if ($superAdminIds->isEmpty()) return;

        // Prepare bulk notification insert
        $message = "New issue in project: {$issue->title} added by: {$issue->user->name}";
        $now = now();

        $notifications = $superAdminIds->map(fn($id) => [
            'user_id' => $id,
            'message' => $message,
            'notifiable_id' => $issue->id,
            'notifiable_type' => ProjectIssue::class,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        // Bulk insert - 1 query
        Notification::insert($notifications);
    }
}
