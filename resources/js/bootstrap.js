import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

const port = Number(import.meta.env.VITE_REVERB_PORT ?? 8080);

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: port,
    wssPort: port,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Debug koneksi Reverb
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('[JANETA] Reverb connected');
});

window.Echo.connector.pusher.connection.bind('connecting', () => {
    console.log('[JANETA] Connecting to Reverb...');
});

window.Echo.connector.pusher.connection.bind('error', (error) => {
    console.error('[JANETA] Reverb connection error:', error);
});


/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
