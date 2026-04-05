<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Message;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public string $roomId
    ) {}

    /**
     * ✅ BROADCAST A MÚLTIPLES CANALES
     * - chat.{roomId}: Para cuando el chat está abierto
     * - user.{receiver_id}: Para notificaciones globales (sidebar/empty state)
     * - user.{sender_id}: Para sincronización multi-pestaña
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("chat.{$this->roomId}"),
        ];

        if (!empty($this->message->receiver_id)) {
            $channels[] = new PrivateChannel("user.{$this->message->receiver_id}");
        }

        if (!empty($this->message->sender_id)) {
            $channels[] = new PrivateChannel("user.{$this->message->sender_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
                'receiver_id' => $this->message->receiver_id,
                'message' => $this->message->message,
                'is_read' => $this->message->is_read,
                'created_at' => $this->message->created_at->toISOString(),
                'sender' => [
                    'id' => $this->message->sender->id,
                    'name' => $this->message->sender->name,
                    'username' => $this->message->sender->username,
                    'avatar' => $this->message->sender->avatar ?? null, // base64 o null
                ]
            ],
            'room_id' => $this->roomId
        ];
    }
}