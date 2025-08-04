<?php

namespace App\Listeners;

use App\Events\LeaveValidated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLeaveValidatedNotification
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
    public function handle(LeaveValidated $event): void
    {
        $leave = $event->leave->load([
            'status', 
            'leaveCategory'
        ]);

        $leave = $event->leave;
        $submitterId = $leave->user_id;
        $categoryName = strtolower($leave->leaveCategory->name);

        $message = "Your {$categoryName} leave request has been {$leave->status->status_name}.";

        // Use the polymorphic relation
        $leave->notifications()->create([
            'user_id' => $submitterId,
            'message' => $message,
        ]);
        
    }
}
