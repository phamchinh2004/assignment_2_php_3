import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/user.css',
                'resources/css/user/home.css',
                'resources/css/user/winwheel/main.css',
                'resources/js/user/home.js',
                'resources/css/user/me.css',
                'resources/js/user/me.js',
                'resources/css/user/order.css',
                'resources/js/user/order.js',
                'resources/css/user/personal_information.css',
                'resources/css/user/vip.css',
                'resources/css/user/withdraw_money.css',
                'resources/js/general.js',
                'resources/css/general.css',
                'resources/css/user/balance_fluctuation.css',
                'resources/js/user/balance_fluctuation.js',
                'resources/css/user/distribution.css'
            ],
            refresh: true,
        }),
    ],
});