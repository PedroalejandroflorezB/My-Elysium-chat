<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'is_admin',
        'avatar',
        'last_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Verificar si el usuario es administrador
     */
    public function isAdmin(): bool
    {
        return (bool) ($this->is_admin ?? false);
    }

    /**
     * Generar un username único basado en el nombre.
     * Estrategia:
     *  1. nombre limpio           → "maria_garcia"
     *  2. nombre + sufijo corto   → "maria_garcia_mx" / "maria_garcia_dev"
     *  3. nombre + 2 dígitos      → "maria_garcia_42"
     *  4. nombre + 4 dígitos      → "maria_garcia_1984"
     *  5. fallback con timestamp  → "user_1714000000"
     */
    public static function generateUniqueUsername(string $name): string
    {
        $clean = trim($name);
        if (empty($clean)) $clean = 'user';

        // Slug base: "María García" → "maria_garcia"
        $base = \Illuminate\Support\Str::slug($clean, '_');
        if (empty($base)) $base = 'user';

        // Limitar longitud base a 20 chars
        $base = substr($base, 0, 20);

        // 1. Intentar el nombre limpio directamente
        if (!self::where('username', $base)->exists()) {
            return $base;
        }

        // 2. Sufijos cortos naturales
        $suffixes = ['mx', 'dev', 'pro', 'ok', 'go', 'app', 'net', 'io', 'co', 'hub'];
        foreach ($suffixes as $suffix) {
            $candidate = "{$base}_{$suffix}";
            if (!self::where('username', $candidate)->exists()) {
                return $candidate;
            }
        }

        // 3. Dos dígitos aleatorios (10 intentos)
        for ($i = 0; $i < 10; $i++) {
            $candidate = $base . '_' . rand(10, 99);
            if (!self::where('username', $candidate)->exists()) {
                return $candidate;
            }
        }

        // 4. Cuatro dígitos aleatorios (10 intentos)
        for ($i = 0; $i < 10; $i++) {
            $candidate = $base . '_' . rand(1000, 9999);
            if (!self::where('username', $candidate)->exists()) {
                return $candidate;
            }
        }

        // 5. Fallback garantizado con timestamp
        return 'user_' . time();
    }

    /**
     * Retorna la URL del avatar (base64 directo o URL de storage)
     */
    public function getAvatarUrl(): ?string
    {
        if (!$this->avatar) return null;
        // Avatares son siempre base64 en este sistema
        if (str_starts_with($this->avatar, 'data:')) return $this->avatar;
        return null; // legacy — no usar storage
    }
    protected static function boot()
    {
        parent::boot();
        
        // Antes de crear, asegurar que tenga username único
        static::creating(function ($user) {
            if (empty($user->username)) {
                $user->username = self::generateUniqueUsername($user->name);
            }
        });
        
        // Antes de actualizar, verificar unicidad si cambió el username
        static::updating(function ($user) {
            if ($user->isDirty('username') && !empty($user->username)) {
                $existingUser = self::where('username', $user->username)
                    ->where('id', '!=', $user->id)
                    ->first();
                    
                if ($existingUser) {
                    throw new \InvalidArgumentException("El username '{$user->username}' ya está en uso.");
                }
            }
        });
    }

    /**
     * Los contactos del usuario (relación muchos a muchos)
     * Tabla pivote: contacts
     * 
     * @return BelongsToMany
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class, 
            'contacts',      // ← tabla pivote
            'user_id',       // ← columna de este usuario
            'contact_id'     // ← columna del otro usuario
        )
        ->using(\App\Models\Contact::class)  // ← IMPORTANTE: usar modelo pivote
        ->withTimestamps();
    }

    /**
     * Solicitudes de contacto enviadas
     * 
     * @return HasMany
     */
    public function sentContactRequests(): HasMany
    {
        return $this->hasMany(ContactRequest::class, 'sender_id');
    }

    /**
     * Solicitudes de contacto recibidas
     * 
     * @return HasMany
     */
    public function receivedContactRequests(): HasMany
    {
        return $this->hasMany(ContactRequest::class, 'receiver_id');
    }

    /**
     * Mensajes enviados
     * 
     * @return HasMany
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Mensajes recibidos
     * 
     * @return HasMany
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }
}