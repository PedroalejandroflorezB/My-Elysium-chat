<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class WebRTCIceCandidate implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public $fromUserId;
    public $toUserId;
    public $candidate;
    
    public function __construct($fromUserId, $toUserId, $candidate)
    {
        $this->fromUserId = $fromUserId;
        $this->toUserId = $toUserId;
        $this->candidate = $candidate;
    }
    
    public function broadcastOn()
    {
        return new \Illuminate\Broadcasting\PrivateChannel('user.' . $this->toUserId);
    }

    public function broadcastAs()
    {
        return 'WebRTCIceCandidate';
    }
}