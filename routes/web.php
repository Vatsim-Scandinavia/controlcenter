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
Route::get('/', function(){
    return view('front');
});
//--------------------------------------------------------------------------
// VATSIM Authentication
//--------------------------------------------------------------------------
Route::get('/login', 'Auth\LoginController@login')->middleware('guest')->name('login');
Route::get('/validate', 'Auth\LoginController@validateLogin')->middleware('guest');
Route::get('/logout', 'Auth\LoginController@logout')->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', 'DashboardController@index')->name('dashboard');

});

/*
Auth::routes();

Route::get('/home', 'DashboardController@index')->name('home');
*/