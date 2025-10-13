<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public Room $room;

    /** @var array<int, array{id:int,name:string,color:string,isHost:bool}> */
    public array $players;

    /**
     * @param  Room  $room
     * @param  array<int, array{id:int,name:string,color:string,isHost:bool}>|null  $playersPayload
     */
    public function __construct(Room $room, ?array $playersPayload = null)
    {
        // Make sure relations are available for building a payload when needed
        $this->room = $room->loadMissing('players', 'creator');

        // If a payload was provided by the controller, use it; otherwise build it here
        $this->players = $playersPayload ?? $this->room->players->map(function ($p) {
            return [
                'id'     => $p->id,
                'name'   => $p->name,
                'color'  => $p->pivot->color ?? '#ccc',
                'isHost' => $p->id === $this->room->creator->id,
            ];
        })->values()->all();
    }

    public function broadcastOn(): Channel
    {
        // broadcast to this room’s channel (matches your JS: Echo.channel(`room.${roomId}`))
        return new Channel('room.' . $this->room->id);
    }

    public function broadcastAs(): string
    {
        return 'RoomUpdated';
    }

    public function broadcastWith(): array
    {
        // Keep the payload lean but complete for the UI
        return [
            'roomId'  => $this->room->id,
            'players' => $this->players,
        ];
    }
}