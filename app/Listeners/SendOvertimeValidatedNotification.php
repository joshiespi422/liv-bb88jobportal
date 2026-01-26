<?php

namespace App\Listeners;

use App\Events\OvertimeValidated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Notification;
use App\Models\Overtime;

class SendOvertimeValidatedNotification
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
    public function handle(OvertimeValidated $event): void
    {
        $overtime = $event->overtime->loadMissing([
            'requester:id,name',
        ]);
        $requesterId = $overtime->requester_id;
        $signerId = $overtime->signer_id;

        
        $recipientIds = collect([$requesterId, $signerId])
            ->unique()
            ->filter();
        
        // Prepare notification message
        $message = "Material request for {$overtime->date} requested by: {$overtime->requester->name} has been validated";
        $now = now();

        // Prepare an array of notification data, bulk insert
        $notifications = $recipientIds->map(fn($id) => [
            'user_id' => $id,
            'message' => $message,
            'notifiable_id' => $overtime->id,
            'notifiable_type' => Overtime::class,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        // Use a single query to insert all notifications
        Notification::insert($notifications);
    }
}
