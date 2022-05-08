const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js([
    'resources/js/app.js',
    'node_modules/bootstrap-table/dist/bootstrap-table.js',
    'node_modules/bootstrap-table/dist/locale/bootstrap-table-en-US.js',
    'node_modules/bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.js',
    'node_modules/bootstrap-table/dist/extensions/cookie/bootstrap-table-cookie.js',
    'node_modules/moment/moment.js',
], 'public/js').vue()
.sass('resources/sass/app.scss', 'public/css', {
    additionalData: '$envColorPrimary: ' + (process.env.BOOTSTRAP_COLOR_PRIMARY || '#1a475f') + '; $envColorSecondary: ' + (process.env.BOOTSTRAP_COLOR_SECONDARY || '#484b4c') + '; $envColorTertiary: ' + (process.env.BOOTSTRAP_COLOR_TERTIARY || '#011328') + '; $envColorInfo: ' + (process.env.BOOTSTRAP_COLOR_INFO || '#17a2b8') + '; $envColorSuccess: ' + (process.env.BOOTSTRAP_COLOR_SUCCESS || '#41826e') + '; $envColorWarning: ' + (process.env.BOOTSTRAP_COLOR_WARNING || '#ff9800') + '; $envColorDanger: ' + (process.env.BOOTSTRAP_COLOR_DANGER || '#b63f3f') + '; $envBorderRadius: ' + (process.env.BOOTSTRAP_BORDER_RADIUS || '0px') + ';',
})
.sass('resources/sass/vendor.scss', 'public/css')