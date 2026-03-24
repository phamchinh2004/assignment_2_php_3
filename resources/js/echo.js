import Echo from 'laravel-echo';
import * as bootstrap from 'bootstrap'; // lấy đúng đối tượng bootstrap
window.bootstrap = bootstrap;
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