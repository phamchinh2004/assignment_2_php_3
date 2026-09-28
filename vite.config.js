import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import glob from 'fast-glob';

export default defineConfig({
    esbuild: {
        jsx: 'automatic',
    },
    plugins: [
        laravel({
            input: [
                ...glob.sync('resources/css/**/*.css'),
                ...glob.sync('resources/js/**/*.js'),
                'resources/js/react/main.jsx',
            ],
            refresh: true,
        }),
    ],
});
