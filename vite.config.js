import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import fs from 'fs';
import path from 'path';

export default ({ mode }) => {

    // Process the environment variables and generate a SCSS baseline for theming
    process.env = {...process.env, ...loadEnv(mode, process.cwd())};

    const scssContent = `
        // Do not change this file, it will be overwritten!
        // Theming variables generated from .env while building front-end
        $envColorPrimary: ${process.env.VITE_THEME_PRIMARY || '#1a475f'};
        $envColorSecondary: ${process.env.VITE_THEME_SECONDARY || '#484b4c'};
        $envColorTertiary: ${process.env.VITE_THEME_TERTIARY || '#011328'};
        $envColorInfo: ${process.env.VITE_THEME_INFO || '#17a2b8'};
        $envColorSuccess: ${process.env.VITE_THEME_SUCCESS || '#41826e'};
        $envColorWarning: ${process.env.VITE_THEME_WARNING || '#ff9800'};
        $envColorDanger: ${process.env.VITE_THEME_DANGER || '#b63f3f'};
        $envBorderRadius: ${process.env.VITE_THEME_BORDER_RADIUS || '2px'};
    `.replace(/^\s+/gm, '');
    
    fs.writeFileSync(__dirname + '/resources/sass/_env.scss', scssContent);

    // Return the Vite configuration
    return defineConfig({
        plugins: [
            vue(),
            laravel({
                input: [
                    "/resources/sass/app.scss",
                    "/resources/js/app.js",
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