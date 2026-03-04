import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['resources/js/tests/setup.js'],
        include: ['resources/js/tests/**/*.{test,spec}.js'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'html'],
            include: [
                'resources/js/stores/**',
                'resources/js/api/**',
                'resources/js/components/**',
                'resources/js/views/**',
            ],
        },
    },
});
