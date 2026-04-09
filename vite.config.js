import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    // Preserve font directory structure from resources/fonts/
                    if (assetInfo.name && (assetInfo.name.endsWith('.otf') || assetInfo.name.endsWith('.woff') || assetInfo.name.endsWith('.woff2') || assetInfo.name.endsWith('.ttf'))) {
                        // Extract path relative to resources/fonts/
                        const name = assetInfo.name || '';
                        if (name.includes('hv muse') || name.includes('hv-muse')) {
                            return 'fonts/hv muse/[name][extname]';
                        }
                        return 'fonts/[name][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
});