<?php

use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\PositionController;
use App\Http\Controllers\API\UserController;

/*
| API v1 routes — mirrors routes/api.php under the api/v1 prefix.
| Points at the same controllers; names are prefixed api.v1.*.
*/

Route::middleware('auth:api')->get('/user', [UserController::class, 'authenticated'])->name('api.v1.user');

Route::group(['middleware' => ['api-token:edit']], function () {
    Route::post('/bookings/create', [BookingController::class, 'store'])->name('api.v1.booking.store');
    Route::patch('/bookings/{booking}', [BookingController::class, 'update'])->name('api.v1.booking.update');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('api.v1.booking.destroy');
});

Route::group(['middleware' => ['api-token']], function () {
    Route::get('/bookings', [BookingController::class, 'index'])->name('api.v1.booking.index');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('api.v1.booking.show');
    Route::get('/positions', [PositionController::class, 'index'])->name('api.v1.positions.index');
    Route::get('/users', [UserController::class, 'index'])->name('api.v1.users.index');
});
