/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

if (reverbKey) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https' ? ['ws', 'wss'] : ['ws'],
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    });
    window.realtimeDisabled = false;
    console.log('✅ Bootstrap loaded, Echo configured');
} else {
    window.realtimeDisabled = true;
    console.warn('⚠️ Pusher/Reverb key is missing. Real-time features disabled (Fallback active)');
    
    // Inyectar aviso discreto en la UI
    document.addEventListener('DOMContentLoaded', () => {
        const notice = document.createElement('div');
        notice.id = 'realtime-disabled-notice';
        notice.innerHTML = '⚡ Modo optimizado (sin tiempo real)';
        notice.style.fontSize = '10px';
        notice.style.opacity = '0.5';
        notice.style.position = 'fixed';
        notice.style.bottom = '10px';
        notice.style.right = '10px';
        notice.style.zIndex = '9999';
        notice.style.pointerEvents = 'none'; // No interferir clics
        document.body.appendChild(notice);
    });
}

