<?php

namespace App\Listeners;

use App\Events\ProjectIssueResolved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendProjectIssueResolvedNotification
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
    public function handle(ProjectIssueResolved $event): void
    {
        $issue = $event->issue->load([
            'status'
        ]);

        $submitterId = $issue->user_id;

        $message = "Your issue in project {$issue->title} has been {$issue->status->status_name}.";

        // Use the polymorphic relation
        $issue->notifications()->create([
            'user_id' => $submitterId,
            'message' => $message,
        ]);

    }
}
