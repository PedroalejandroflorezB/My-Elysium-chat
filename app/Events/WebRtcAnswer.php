<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class WebRTCAnswer implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public $fromUserId;
    public $toUserId;
    public $answer;
    
    public function __construct($fromUserId, $toUserId, $answer)
    {
        $this->fromUserId = $fromUserId;
        $this->toUserId = $toUserId;
        $this->answer = $answer;
    }
    
    public function broadcastOn()
    {
        return new \Illuminate\Broadcasting\PrivateChannel('user.' . $this->toUserId);
    }

    public function broadcastAs()
    {
        return 'WebRTCAnswer';
    }
}