<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GameStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $gameId;

    public function __construct(Room $room, $gameId)
    {
        $this->roomId = $room->id;
        $this->gameId = $gameId;
    }

    public function broadcastOn()
    {
        return new Channel("room.{$this->roomId}");
    }

    public function broadcastAs()
    {
        return 'GameStarted';
    }
}


