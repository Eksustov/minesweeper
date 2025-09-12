<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TileUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $row;
    public $col;
    public $action; // "reveal" or "flag"
    public $value;  // true/false for flag or array of cells for reveal
    public $gameOver; // boolean
    public $playerColor;

    /**
     * @param mixed $row/$col can be null for bulk reveals
     */
    public function __construct($roomId, $row = null, $col = null, $action = null, $value = null, $gameOver = false, $playerColor = null)
    {
        $this->roomId = $roomId;
        $this->row = $row;
        $this->col = $col;
        $this->action = $action;
        $this->value = $value;
        $this->gameOver = (bool) $gameOver;
        $this->playerColor = $playerColor;
    }

    public function broadcastOn()
    {
        return new Channel("room.{$this->roomId}");
    }

    public function broadcastAs()
    {
        return 'TileUpdated';
    }
}
