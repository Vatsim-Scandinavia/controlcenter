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
    Route::get('/vatbook', 'DashboardController@vatbook')->name('vatbook');
    Route::get('/content', 'DashboardController@content')->name('content');
    Route::get('/mentor', 'DashboardController@mentor')->name('mentor');
    Route::get('/sweatbox', 'DashboardController@sweatbox')->name('sweatbox');
    Route::get('/requests', 'DashboardController@requests')->name('requests');
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

    // Other routes
    Route::get('/training', 'DashboardController@apply')->name('training');
    Route::get('/training/apply', 'TrainingController@create')->name('training.apply');
    Route::post('/training/store', 'TrainingController@store');

});
