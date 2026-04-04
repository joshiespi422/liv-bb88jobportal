<?php

namespace App\Listeners;

use App\Events\HalfDayValidated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Notification;
use App\Models\HalfDay;

class SendHalfDayValidatedNotification
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
    public function handle(HalfDayValidated $event): void
    {
        $halfDay = $event->halfDay->loadMissing([
            'requester:id,name',
        ]);
        $requesterId = $halfDay->requester_id;
        $signerId = $halfDay->signer_id;

        
        $recipientIds = collect([$requesterId, $signerId])
            ->unique()
            ->filter();
        
        // Prepare notification message
        $message = "Half day request for {$halfDay->date} requested by: {$halfDay->requester->name} has been validated";
        $now = now();

        // Prepare an array of notification data, bulk insert
        $notifications = $recipientIds->map(fn($id) => [
            'user_id' => $id,
            'message' => $message,
            'notifiable_id' => $halfDay->id,
            'notifiable_type' => HalfDay::class,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        // Use a single query to insert all notifications
        Notification::insert($notifications);
    }
}
