import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';
const isSecure = scheme === 'https';

const config = {
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,

    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),

    forceTLS: isSecure,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
};

console.log('[JANETA] Reverb configuration:', {
    host: config.wsHost,
    port: isSecure ? config.wssPort : config.wsPort,
    scheme,
    keyConfigured: Boolean(config.key),
});

window.Echo = new Echo(config);

console.log('[JANETA] Echo initialized');

const connection = window.Echo.connector.pusher.connection;

connection.bind('state_change', (s) => console.log('[JANETA] state:', s.previous, '->', s.current));
connection.bind('connected', () => console.log('[JANETA] Reverb connected'));
connection.bind('connecting', () => console.log('[JANETA] Connecting to Reverb...'));
connection.bind('disconnected', () => console.warn('[JANETA] Reverb disconnected'));
connection.bind('unavailable', () => console.warn('[JANETA] Reverb unavailable'));
connection.bind('failed', () => console.warn('[JANETA] Reverb failed'));
connection.bind('error', (error) => console.error('[JANETA] Reverb connection error:', error));
