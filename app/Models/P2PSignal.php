<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class P2PSignal extends Model
{
    protected $table = 'p2p_signals';

    protected $fillable = [
        'from_id',
        'to_id',
        'type',
        'data',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime'
    ];
}
