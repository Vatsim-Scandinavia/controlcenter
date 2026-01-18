import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default ({ mode }) => {

    // Return the Vite configuration
    return defineConfig({
        plugins: [
            vue(),
            laravel({
                input: [
                    "/resources/sass/app.scss",
                    "/resources/js/app.js",
                    "/resources/js/theme.js",
                    "/resources/js/vue.js",
                    "/resources/js/easymde.js",
                    "/resources/sass/easymde.scss",
                    "/resources/js/chart.js",
                    "/resources/js/flatpickr.js",
                    "/resources/sass/flatpickr.scss",
                    "/resources/js/bootstrap-table.js",
                    "/resources/sass/bootstrap-table.scss",
                ],
                refresh: true,
            }),
        ],
        resolve: {
            alias: {
                vue: 'vue/dist/vue.esm-bundler.js',
                '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
                '@': '/resources/js',
            },
        },
    });
}