<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id', 
        'message',
        'is_read',
        'read_at',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'deleted_for_sender',
        'deleted_for_receiver',
    ];
    
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime'
    ];
    
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
    
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
    
    public function getRoomIdAttribute(): string
    {
        // Room ID único para cada par de usuarios (ordenado para consistencia)
        $ids = collect([$this->sender_id, $this->receiver_id])->sort()->implode('_');
        return "chat_{$ids}";
    }
}