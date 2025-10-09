<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $board;
    public $rows;
    public $cols;
    public $mines;

    public function __construct($roomId, $board, $rows, $cols, $mines)
    {
        $this->roomId = $roomId;
        $this->board  = $board;   // <-- important: array, not json string
        $this->rows   = $rows;
        $this->cols   = $cols;
        $this->mines  = $mines;
    }

    public function broadcastOn()
    {
        return new Channel("room.{$this->roomId}");
    }

    public function broadcastAs()
    {
        return 'GameStarted';
    }

    public function broadcastWith()
    {
        return [
            'board' => $this->board, // send as array
            'rows'  => $this->rows,
            'cols'  => $this->cols,
            'mines' => $this->mines,
        ];
    }
}
