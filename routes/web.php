<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//--------------------------------------------------------------------------
// Main page
//--------------------------------------------------------------------------
Route::get('/', 'FrontPageController@index')->name('front');

//--------------------------------------------------------------------------
// VATSIM Authentication
//--------------------------------------------------------------------------
Route::get('/login', 'Auth\LoginController@login')->middleware('guest')->name('login');
Route::get('/validate', 'Auth\LoginController@validateLogin')->middleware('guest');
Route::get('/logout', 'Auth\LoginController@logout')->middleware('auth')->name('logout');

//--------------------------------------------------------------------------
// Sites behind authentication
//--------------------------------------------------------------------------
Route::middleware('auth')->group(function () {

    // Sidebar Navigation
    Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
    Route::get('/content', 'DashboardController@content')->name('content');
    Route::get('/mentor', 'DashboardController@mentor')->name('mentor');
    Route::get('/trainings', 'TrainingController@index')->name('requests');
    Route::get('/users', 'UserController@index')->name('users');
    Route::get('/users/endorsements', 'DashboardController@member')->name('users.endorsements');

    Route::get('/reports/stats', 'DashboardController@reports')->name('reports.stats');
    Route::get('/reports/mentors', 'DashboardController@reports')->name('reports.mentors');
    Route::get('/reports/atc', 'DashboardController@reports')->name('reports.atc');

    Route::get('/admin/settings', 'DashboardController@admin')->name('admin.settings');
    Route::get('/admin/content', 'DashboardController@admin')->name('admin.content');
    Route::get('/admin/templates', 'DashboardController@admin')->name('admin.templates');

    // User-specific Navigation
    Route::get('/settings', 'UserSettingController@index')->name('settings');

    // Training routes
    Route::get('/training/apply', 'TrainingController@create')->name('training.apply');
    Route::post('/training/store', 'TrainingController@store');
    Route::post('/training/update', 'TrainingController@update');
    Route::get('/training/{training}', 'TrainingController@show');

    // Sweatbox routes
    Route::get('/sweatbox', 'SweatboxController@index')->name('sweatbox');
    Route::get('/sweatbox/create', 'SweatboxController@create')->name('sweatbox.create');
    Route::get('/sweatbox/{id}/delete', 'SweatboxController@delete')->name('sweatbox.delete');
    Route::get('/sweatbox/{id}', 'SweatboxController@show');
    Route::post('/sweatbox/store', 'SweatboxController@store');
    Route::post('/sweatbox/update', 'SweatboxController@update');

    // Vatbook routes
    Route::get('/vatbook', 'VatbookController@index')->name('vatbook');
    Route::get('/vatbook/create', 'VatbookController@create')->name('vatbook.create');
    Route::get('/vatbook/{id}/delete', 'VatbookController@delete')->name('vatbook.delete');
    Route::get('/vatbook/{id}', 'VatbookController@show');
    Route::post('/vatbook/store', 'VatbookController@store');
    Route::post('/vatbook/update', 'VatbookController@update');
});
