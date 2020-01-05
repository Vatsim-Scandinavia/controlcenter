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
/*
mix.js([
    'resources/js/app.js',
    'node_modules/chart.js/dist/Chart.bundle.js',
    'node_modules/bootstrap-table/dist/bootstrap-table.js',
    'node_modules/bootstrap-table/dist/locale/bootstrap-table-en-US.js'
], 'public/js')
.sass('resources/sass/app.scss', 'public/css/app.css')
.styles([
    'resources/sass/app.css',
    'node_modules/chart.js/dist/Chart.css',
    'node_modules/bootstrap-table/dist/bootstrap-table.css',
], 'public/css/style.css')
*/

mix.js([
    'resources/js/app.js',
    'node_modules/chart.js/dist/Chart.bundle.js',
    'node_modules/bootstrap-table/dist/bootstrap-table.js',
    'node_modules/bootstrap-table/dist/locale/bootstrap-table-en-US.js',
    'node_modules/bootstrap-table/dist/extensions/filter-control/bootstrap-table-filter-control.js'
], 'public/js')
.sass('resources/sass/app.scss', 'public/css')
.sass('resources/sass/vendor.scss', 'public/css')