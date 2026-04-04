<?php

namespace App\Listeners;

use App\Events\HalfDayCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use App\Models\Notification;
use App\Models\HalfDay;
use Illuminate\Database\Eloquent\Builder;

class SendNewHalfDayNotification
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
    public function handle(HalfDayCreated $event): void
    {
        $halfDay = $event->halfDay->loadMissing([
            'requester:id,name',
            'status:id,status_name',
        ]);
        $submitterId = $halfDay->requester_id;
        $status = $halfDay->status->status_name;

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
        $message = "New half day for {$halfDay->date} has been submitted by {$halfDay->requester->name}.";
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
