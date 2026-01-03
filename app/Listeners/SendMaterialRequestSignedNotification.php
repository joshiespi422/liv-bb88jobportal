<?php

namespace App\Listeners;

use App\Events\MaterialRequestSigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use App\Models\MaterialRequest;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;

class SendMaterialRequestSignedNotification
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
    public function handle(MaterialRequestSigned $event): void
    {
        $materialRequest = $event->materialRequest->loadMissing([
            'signer:id,name',
        ]);

        // 1. Get super admin IDs - 1 query
        $superAdminIds = User::whereHas('userType', fn(Builder $q) => 
            $q->where('type_name', 'super_admin')
        )->pluck('id');

        // Combine and deduplicate IDs - in-memory processing
        $recipientIds = collect($superAdminIds)
            ->merge([$materialRequest->requested_by])
            ->unique()
            ->filter()
            ->reject(fn($id) => $id === $materialRequest->signed_by);

        if ($recipientIds->isEmpty()) return;

        // Prepare notification message
        $message = "Material request {$materialRequest->name} has been signed by: {$materialRequest->signer->name}";
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
