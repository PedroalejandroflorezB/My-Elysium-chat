<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $deletedBy;
    public $receiverId;
    
    public function __construct($roomId, $deletedBy, $receiverId = null)
    {
        $this->roomId    = $roomId;
        $this->deletedBy = $deletedBy;
        $this->receiverId = $receiverId;
    }
    
    /**
     * ✅ BROADCAST A MÚLTIPLES CANALES
     * - chat.{roomId}: Para cuando el chat está abierto
     * - user.{deletedBy}: Para el que borró (otras pestañas)
     * - user.{receiverId}: Para el otro usuario (notificación global)
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('chat.' . $this->roomId),
        ];
        
        if (!empty($this->deletedBy)) {
            $channels[] = new PrivateChannel('user.' . $this->deletedBy);
        }
        
        if (!empty($this->receiverId)) {
            $channels[] = new PrivateChannel('user.' . $this->receiverId);
        }
        
        return $channels;
    }
    
    public function broadcastAs(): string
    {
        return 'chat.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'deleted_by' => $this->deletedBy,
            'receiver_id' => $this->receiverId
        ];
    }
}

