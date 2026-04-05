<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class P2PSignaling implements ShouldBroadcastNow
{
    public function __construct(
        public string $from,
        public string $to,
        public string $type,
        public array $data
    ) {}

    /**
     * Canal donde se emite el evento.
     *
     * IMPORTANTE: Usar PrivateChannel (NO Channel público) porque el frontend
     * se suscribe con Echo.private('p2p.{peerId}').
     *
     * Reverb enviará el evento al canal 'private-p2p.{peerId}' y
     * channels.php autoriza el acceso bajo la clave 'p2p.{peerId}'.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->to),
        ];
    }

    /**
     * Nombre del evento en el frontend.
     * El frontend escucha: .listen('.p2p.offer', ...), .listen('.p2p.transfer.request', ...)
     */
    public function broadcastAs(): string
    {
        return 'p2p.' . $this->type;
    }

    /**
     * Datos que se envían al frontend.
     */
    public function broadcastWith(): array
    {
        return [
            'from' => $this->from,
            'data' => $this->data,
        ];
    }
}
