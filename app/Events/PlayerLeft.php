<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PlayerLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $players; // updated list of players
    public $roomId;

    public function __construct(Room $room)
    {
        $this->players = $room->players
            ->map(fn($player) => [
                'id' => $player->id,
                'name' => $player->name,
                'color' => $player->pivot->color,
            ]);
        $this->roomId = $room->id;
    }

    public function broadcastOn()
    {
        return new Channel("room.{$this->roomId}");
    }

    public function broadcastAs()
    {
        return 'PlayerLeft';
    }
}
