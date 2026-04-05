/**
 * Laravel Echo - Reverb Configuration
 * Elysium P2P - WebSocket Real-time
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Hacer Pusher disponible globalmente (CRÍTICO para Echo)
if (typeof window !== 'undefined') {
    window.Pusher = Pusher;
}

// Configuración para Laravel Reverb
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_SCHEME === 'https' ? 443 : (import.meta.env.VITE_REVERB_PORT ?? 80),
    wssPort: import.meta.env.VITE_REVERB_SCHEME === 'https' ? (import.meta.env.VITE_REVERB_PORT ?? 443) : 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https' ? ['wss'] : ['ws'],
    disableStats: true,
});

export default Echo;