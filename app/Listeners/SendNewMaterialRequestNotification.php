<?php

namespace App\Listeners;

use App\Events\MaterialRequestCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use App\Models\Notification;
use App\Models\MaterialRequest;
use Illuminate\Database\Eloquent\Builder;

class SendNewMaterialRequestNotification
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
    public function handle(MaterialRequestCreated $event): void
    {
        $materialRequest = $event->materialRequest->loadMissing([
            'requester:id,name',
            'status:id,status_name',
        ]);
        $submitterId = $materialRequest->requested_by;
        $status = $materialRequest->status->status_name;

        if ($status === 'for approval') {
            // 1. Get super admin IDs - 1 query
            $recipientIds = User::whereHas('userType', fn(Builder $q) => 
                $q->where('type_name', 'super_admin')
            )->pluck('id');
        } elseif ($status === 'pending') {
            // 1. Get employee head IDs - 1 query
            $recipientIds = User::whereHas('employeeDetails', fn(Builder $q) => 
                $q->where('is_head', true)
            )->pluck('id');
        } else {
            return;
        }

        // Exclude submitter
        $recipientIds = $recipientIds->reject(fn($id) => $id === $submitterId);
        if ($recipientIds->isEmpty()) return;

        // Prepare notification message
        $message = "New material request {$materialRequest->name} has been submitted by {$materialRequest->requester->name}.";
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
