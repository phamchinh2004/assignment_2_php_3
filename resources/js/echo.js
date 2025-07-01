console.log('Echo initialized');
import Echo from 'laravel-echo';
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Pusher from 'pusher-js';
import axios from 'axios';
axios.defaults.withCredentials = true;
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    withCredentials: true, // QUAN TRỌNG!
    authEndpoint: '/broadcasting/auth',
});
window.Echo.connector.pusher.connection.bind('state_change', (states) => {
    console.log('WebSocket state:', states);
});
Livewire.start();