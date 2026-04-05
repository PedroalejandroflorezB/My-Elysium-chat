<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    // Tabla explícita (por si no sigue la convención)
    protected $table = "contacts";
    
    // Campos asignables en masa
    protected $fillable = ["user_id", "contact_id"];
    
    // Timestamps activados
    public $timestamps = true;
    
    /**
     * Usuario que tiene este contacto (el "dueño" de la relación)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }
    
    /**
     * Usuario que ES el contacto (el "amigo")
     * ← ESTA ES LA RELACIÓN QUE FALTABA
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(User::class, "contact_id");
    }
}