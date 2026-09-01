<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductImeiEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $imeiId;
    public $branchId;
    public $eventType;
    public $description;

    public function __construct($imeiId, $branchId, $eventType, $description)
    {
        $this->imeiId = $imeiId;
        $this->branchId = $branchId;
        $this->eventType = $eventType;
        $this->description = $description;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
