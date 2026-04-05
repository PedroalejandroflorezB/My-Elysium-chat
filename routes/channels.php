<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| NOTA: Broadcast::routes() NO debe llamarse aquí cuando ya está registrado
| en BroadcastServiceProvider o config/broadcasting.php. Laravel lo hace
| automáticamente cuando middleware 'web' incluye Authenticate.
|
*/

/**
 * Canal privado para notificaciones de usuario.
 * Echo.private('user.{id}') → Laravel busca 'user.{userId}' aquí.
 */
Broadcast::channel('user.{userId}', function (User $user, $userId) {
    return (int) $user->id === (int) $userId;
});

/**
 * Canal de chat privado entre dos usuarios.
 * Echo.private('chat.{roomId}') → Laravel busca 'chat.{roomId}' aquí.
 */
Broadcast::channel('chat.{roomId}', function (User $user, $roomId) {
    if (preg_match('/^chat_(\d+)_(\d+)$/', $roomId, $matches)) {
        $user1 = (int) $matches[1];
        $user2 = (int) $matches[2];
        return $user->id === $user1 || $user->id === $user2;
    }
    return false;
});

/**
 * Canal de presencia para chat (presence channels).
 * Echo.join('presence-chat.{roomId}') → Laravel busca 'presence-chat.{roomId}' aquí.
 */
Broadcast::channel('presence-chat.{roomId}', function (User $user, $roomId) {
    if (preg_match('/^chat_(\d+)_(\d+)$/', $roomId, $matches)) {
        $user1 = (int) $matches[1];
        $user2 = (int) $matches[2];
        if ($user->id === $user1 || $user->id === $user2) {
            return [
                'id'       => $user->id,
                'name'     => $user->name,
                'username' => $user->username,
                'avatar'   => $user->avatar ?? null, // base64 o null
            ];
        }
    }
    return false;
});
/**
 * Canal de presencia global para ver quién está en línea.
 */
Broadcast::channel('presence-global', function (User $user) {
    if (auth()->check()) {
        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'username' => $user->username,
            'avatar'   => $user->avatar ?? null, // base64 o null
        ];
    }
    return false;
});
