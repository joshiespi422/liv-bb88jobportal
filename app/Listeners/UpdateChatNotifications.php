<?php

namespace App\Listeners;

use App\Events\ChatMessageCreated;
use App\Models\ChatMessage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateChatNotifications // implements ShouldQueue // reliant on cron for shared hosting
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    // use InteractsWithQueue; // reliant on cron for shared hosting

    public function handle(ChatMessageCreated $event): void
    {
        $chatMessage = $event->chatMessage;
        $group = $event->group;
        $senderId = $chatMessage->user_id;
        
        // 1. Get the Query Builder (NOT the collection) for target users
        $userQuery = $this->getTargetUsersQuery($group, $senderId);

        // 2. Process in chunks of 500 to keep RAM usage strictly controlled
        $userQuery->chunk(500, function ($users) use ($chatMessage, $group) {
            $userIds = $users->pluck('id')->toArray();

            // Fetch existing notifications ONLY for this chunk of 500 users
            $existingNotifications = Notification::whereIn('user_id', $userIds)
                ->where('notifiable_type', ChatMessage::class)
                ->whereHasMorph('notifiable', [ChatMessage::class], function ($query) use ($group) {
                    $query->where('group', $group);
                })
                ->get()
                ->keyBy('user_id');

            $now = now()->toDateTimeString(); // Use string for bulk inserts/upserts
            $inserts = [];
            $updates = [];

            foreach ($userIds as $userId) {
                if ($existingNotifications->has($userId)) {
                    $notification = $existingNotifications->get($userId);
                    
                    if ($notification->read) {
                        $count = 1;
                    } else {
                        preg_match('/have (\d+) chat/', $notification->message, $matches);
                        $count = isset($matches[1]) ? (int)$matches[1] + 1 : 2;
                    }

                    $msgWord = $count === 1 ? 'message' : 'messages';

                    // Add to updates array (Must include 'id' for upsert to match)
                    $updates[] = [
                        'id' => $notification->id,
                        'user_id' => $userId, // Include required fields
                        'notifiable_type' => ChatMessage::class,
                        'notifiable_id' => $chatMessage->id,
                        'message' => "You have new {$count} chat {$msgWord} in {$group} group",
                        'read' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                } else {
                    // Add to inserts array
                    $inserts[] = [
                        'user_id' => $userId,
                        'notifiable_type' => ChatMessage::class,
                        'notifiable_id' => $chatMessage->id,
                        'message' => "You have new 1 chat message in {$group} group",
                        'read' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // 3. Execute Bulk Database Operations (Max 2 queries per chunk!)
            if (!empty($inserts)) {
                Notification::insert($inserts); // 1 query
            }

            if (!empty($updates)) {
                // Upsert requires the unique column(s) as the second argument ['id']
                // The third argument lists the columns to update if a match is found
                Notification::upsert(
                    $updates, 
                    ['id'], 
                    ['message', 'notifiable_id', 'read', 'created_at', 'updated_at']
                ); // 1 query
            }
        });
    }

    /**
     * Returns the Query Builder (not the executed collection) so we can chunk it.
     */
    private function getTargetUsersQuery(string $group, int $senderId)
    {
        $query = User::where('id', '!=', $senderId);

        if ($group === 'core') {
            $query->where(function ($q) {
                $q->whereHas('userType', fn($t) => $t->where('type_name', 'super_admin'))
                  ->orWhere(function ($sub) {
                      $sub->whereHas('userType', fn($t) => $t->where('type_name', 'employee'))
                          ->whereHas('status', fn($s) => $s->where('status_name', 'active'))
                          ->whereHas('employeeDetails', fn($d) => $d->where('hierarchy', 'Leader'));
                  });
            });
        } elseif ($group === 'employees') {
            $query->whereHas('userType', fn($t) => $t->whereIn('type_name', ['super_admin', 'employee']))
                  ->whereHas('status', fn($s) => $s->where('status_name', 'active'));
        } elseif ($group === 'interns') {
            $query->whereHas('status', fn($s) => $s->whereIn('status_name', ['active', 'ongoing']));
        }

        return $query;
    }
}
