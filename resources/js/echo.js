import Echo from 'laravel-echo';
import * as bootstrap from 'bootstrap'; // lấy đúng đối tượng bootstrap
window.bootstrap = bootstrap;
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Pusher from 'pusher-js';

document.addEventListener('click', () => {
    if (!window.audioContext) {
        window.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        console.log('AudioContext initialized!');
    } else if (window.audioContext.state === 'suspended') {
        window.audioContext.resume();
    }
}, { once: true });
window.playNotificationSound = function (volumn = 0.3, repeat = 1, interval = 800) {
    const audioContext = window.audioContext;
    if (!audioContext) return;

    let count = 0;

    function beep() {
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 880;
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(volumn, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    }

    function loop() {
        if (count >= repeat) return;
        beep();
        count++;
        setTimeout(loop, interval);
    }

    loop();
};


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

Livewire.start();