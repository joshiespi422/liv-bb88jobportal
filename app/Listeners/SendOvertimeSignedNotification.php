<?php

namespace App\Listeners;

use App\Events\OvertimeSigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use App\Models\Overtime;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;

class SendOvertimeSignedNotification
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
    public function handle(OvertimeSigned $event): void
    {
        $overtime = $event->overtime->loadMissing([
            'signer:id,name',
        ]);

        // 1. Get super admin IDs - 1 query
        $superAdminIds = User::whereHas('userType', fn(Builder $q) => 
            $q->where('type_name', 'super_admin')
        )->pluck('id');

        // Combine and deduplicate IDs - in-memory processing
        $recipientIds = collect($superAdminIds)
            ->merge([$overtime->requester_id])
            ->unique()
            ->filter()
            ->reject(fn($id) => $id === $overtime->signer_id);

        if ($recipientIds->isEmpty()) return;

        // Prepare notification message
        $message = "Overtime request {$overtime->date} has been signed by: {$overtime->signer->name}";
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
