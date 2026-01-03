<?php

namespace App\Listeners;

use App\Events\MaterialRequestValidated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Notification;
use App\Models\MaterialRequest;

class SendMaterialRequestValidatedNotification
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
    public function handle(MaterialRequestValidated $event): void
    {
        $materialRequest = $event->materialRequest->loadMissing([
            'requester:id,name',
        ]);
        $requesterId = $materialRequest->requested_by;
        $signerId = $materialRequest->signed_by;

        
        $recipientIds = collect([$requesterId, $signerId])
            ->unique()
            ->filter();
        
        // Prepare notification message
        $message = "Material request {$materialRequest->name} requested by: {$materialRequest->requester->name} has been validated";
        $now = now();

        // Prepare an array of notification data, bulk insert
        $notifications = $recipientIds->map(fn($id) => [
            'user_id' => $id,
            'message' => $message,
            'notifiable_id' => $materialRequest->id,
            'notifiable_type' => MaterialRequest::class,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        // Use a single query to insert all notifications
        Notification::insert($notifications);
        
    }
}
