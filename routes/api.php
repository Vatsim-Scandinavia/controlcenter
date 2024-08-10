<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['api-token:edit']], function () {
    Route::post('/bookings/create', [App\Http\Controllers\API\BookingController::class, 'store'])->name('api.booking.store');
    Route::patch('/bookings/{booking}', [App\Http\Controllers\API\BookingController::class, 'update'])->name('api.booking.update');
    Route::delete('/bookings/{booking}', [App\Http\Controllers\API\BookingController::class, 'destroy'])->name('api.booking.destroy');
});

Route::group(['middleware' => ['api-token']], function () {
    Route::get('/bookings', [App\Http\Controllers\API\BookingController::class, 'index'])->name('api.booking.index');
    Route::get('/bookings/{booking}', [App\Http\Controllers\API\BookingController::class, 'show'])->name('api.booking.show');
    Route::get('/positions', [App\Http\Controllers\API\PositionController::class, 'index'])->name('api.positions.index');

    Route::get('/users', [App\Http\Controllers\API\UserController::class, 'index'])->name('api.users.index');
});
