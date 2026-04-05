<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagesMarkedAsRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $senderId;
    public $receiverId;

    /**
     * Create a new event instance.
     */
    public function __construct($roomId, $senderId, $receiverId)
    {
        $this->roomId = $roomId;
        $this->senderId = $senderId; // El que envió los mensajes (el que verá los tickets azules)
        $this->receiverId = $receiverId; // El que los leyó (el usuario actual)
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->roomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messages.read';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'read_at' => now()->toISOString(),
        ];
    }
}
