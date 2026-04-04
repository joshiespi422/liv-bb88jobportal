<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\HalfDay;

class HalfDayValidated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The half day instance.
     *
     * @var \App\Models\HalfDay
     */
    public $halfDay;

    /**
     * Create a new event instance.
     */
    public function __construct(HalfDay $halfDay)
    {
        $this->halfDay = $halfDay;
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
