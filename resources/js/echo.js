import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
    withCredentials: true,
    authEndpoint: '/broadcasting/auth',
});

const pusherConnection = window.Echo.connector?.pusher?.connection;
if (pusherConnection) {
    pusherConnection.bind('state_change', (states) => {
        console.debug('[Echo] connection:', states.previous, '->', states.current);
    });
    pusherConnection.bind('error', (error) => {
        console.error('[Echo] connection error:', error);
    });
}

window.dispatchEvent(new CustomEvent('echo:ready'));