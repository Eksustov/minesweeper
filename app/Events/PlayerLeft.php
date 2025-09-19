<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $players; // array of remaining players
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
        return 'PlayerLeft';
    }
}

