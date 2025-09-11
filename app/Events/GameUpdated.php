<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GameUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $row;
    public $col;
    public $action; // 'reveal' or 'flag'
    public $value;  // For flag: true/false, for reveal: mine/count

    public function __construct($roomId, $row, $col, $action, $value)
    {
        $this->roomId = $roomId;
        $this->row = $row;
        $this->col = $col;
        $this->action = $action;
        $this->value = $value;
    }

    public function broadcastOn()
    {
        return new Channel("room.{$this->roomId}");
    }

    public function broadcastAs()
    {
        return 'GameUpdated';
    }
}
