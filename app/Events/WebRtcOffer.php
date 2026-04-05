<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class WebRTCOffer implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public $fromUserId;
    public $toUserId;
    public $offer;
    
    public function __construct($fromUserId, $toUserId, $offer)
    {
        $this->fromUserId = $fromUserId;
        $this->toUserId = $toUserId;
        $this->offer = $offer;
    }
    
    public function broadcastOn()
    {
        return new \Illuminate\Broadcasting\PrivateChannel('user.' . $this->toUserId);
    }

    public function broadcastAs()
    {
        return 'WebRTCOffer';
    }
}