<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PlayerJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $players;
    public $roomId;

    public function __construct(Room $room)
    {
        $this->players = $room->players
        ->map(function($player) {
            $player->color = $player->pivot->color; // now pivot exists
            return [
                'id' => $player->id,
                'name' => $player->name,
                'color' => $player->color,
            ];
        });

        $this->roomId = $room->id;
    }

    public function broadcastOn()
    {
        return new Channel("room.{$this->roomId}");
    }

    public function broadcastAs()
    {
        return 'PlayerJoined';
    }
}