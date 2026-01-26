<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Overtime;

class OvertimeSigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The overtime instance.
     *
     * @var \App\Models\Overtime
     */
    public $overtime;

    /**
     * Create a new event instance.
     */
    public function __construct(Overtime $overtime)
    {
        $this->overtime = $overtime;
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
