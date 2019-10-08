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
    'node_modules/chart.js/dist/Chart.bundle.js',
    'node_modules/datatables.net-bs4/js/dataTables.bootstrap4.js'
], 'public/js')
.sass('resources/sass/app.scss', 'public/css')
.css([
    'node_modules/chart.js/dist/Chart.css',
    'node_modules/datatables.net-bs4/css/dataTables.bootstrap4.css'
], 'public/css')