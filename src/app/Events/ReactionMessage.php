<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ReactionMessage implements ShouldBroadcast
{
    use InteractsWithSockets;

    public $message_id;
    public $reactions;
    public $room_id;

    public function __construct($messageId, $reactions, $roomId)
    {
        $this->message_id = $messageId;
        $this->reactions = $reactions;
        $this->room_id    = $roomId;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('room.' . $this->room_id);
    }
}
