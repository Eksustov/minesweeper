<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class RoomUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $room;

    public function __construct(Room $room)
    {
        $this->room = $room->load('players');
    }

    public function broadcastOn()
    {
        return new Channel('rooms');
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->room->id,
            'code' => $this->room->code,
            'type' => $this->room->type,
            'max_players' => $this->room->max_players,
            'current_players' => $this->room->players->count(),
        ];
    }
}
