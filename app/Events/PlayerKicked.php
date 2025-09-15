<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PlayerKicked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $playerId;

    public function __construct($roomId, $playerId)
    {
        $this->roomId = $roomId;
        $this->playerId = $playerId;
    }

    public function broadcastOn()
    {
        return new Channel("room.{$this->roomId}");
    }

    public function broadcastAs()
    {
        return 'PlayerKicked';
    }
}

