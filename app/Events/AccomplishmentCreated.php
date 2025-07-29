<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Accomplishment;
use App\Models\Task;

class AccomplishmentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Accomplishment $accomplishment;
    public Task $task;

    /**
     * Create a new event instance.
     */
    public function __construct(Accomplishment $accomplishment, Task $task)
    {
        $this->accomplishment = $accomplishment;
        $this->task = $task;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
