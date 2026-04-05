<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactRequestSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $receiverId;
    public $senderData;

    /**
     * Create a new event instance.
     *
     * @param int $receiverId
     * @param array $senderData
     */
    public function __construct($receiverId, $senderData)
    {
        $this->receiverId = $receiverId;
        $this->senderData = $senderData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->receiverId),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'request_id' => $this->senderData['request_id'],
            'id' => $this->senderData['id'],
            'name' => $this->senderData['name'],
            'username' => $this->senderData['username'],
            'avatar' => $this->senderData['avatar'],
            'initials' => $this->senderData['initials'],
        ];
    }
}
