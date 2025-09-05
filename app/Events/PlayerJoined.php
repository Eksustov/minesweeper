<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PlayerJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $players; // only send the players array
    public $room; // add this

    public function __construct(Room $room)
    {
        $this->room = $room;
        $this->players = $room->players()
            ->select('users.id', 'users.name')
            ->get();
    }

    public function broadcastOn()
    {
        return new Channel('room.' . $this->room->id);
    }

    public function broadcastWith()
    {
        return ['players' => $this->players];
    }
}
