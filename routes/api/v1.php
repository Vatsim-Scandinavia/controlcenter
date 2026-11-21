<?php

use App\Http\Controllers\API\AreaController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\EndorsementController;
use App\Http\Controllers\API\PositionController;
use App\Http\Controllers\API\RatingController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\UserController;

/*
| API v1 routes — mirrors routes/api.php under the api/v1 prefix for legacy endpoints,
| then adds management routes that exist only on /api/v1.
*/

Route::middleware('auth:api')->get('/user', [UserController::class, 'authenticated'])->name('api.v1.user');

Route::group(['middleware' => ['api-token:edit']], function () {
    Route::post('/bookings/create', [BookingController::class, 'store'])->name('api.v1.booking.store');
    Route::patch('/bookings/{booking}', [BookingController::class, 'update'])->name('api.v1.booking.update');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('api.v1.booking.destroy');

    Route::post('/positions', [PositionController::class, 'store'])->name('api.v1.positions.store');
    Route::patch('/positions/{position}', [PositionController::class, 'update'])->name('api.v1.positions.update');
    Route::delete('/positions/{position}', [PositionController::class, 'destroy'])->name('api.v1.positions.destroy');

    Route::post('/endorsements', [EndorsementController::class, 'store'])->name('api.v1.endorsements.store');
    Route::delete('/endorsements/{endorsement}', [EndorsementController::class, 'destroy'])->name('api.v1.endorsements.destroy');
    Route::post('/endorsements/{endorsement}/shorten', [EndorsementController::class, 'shorten'])->name('api.v1.endorsements.shorten');

    Route::post('/users/{user}/roles', [RoleController::class, 'store'])->name('api.v1.users.roles.store');
    Route::delete('/users/{user}/roles', [RoleController::class, 'destroy'])->name('api.v1.users.roles.destroy');
});

Route::group(['middleware' => ['api-token']], function () {
    Route::get('/bookings', [BookingController::class, 'index'])->name('api.v1.booking.index');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('api.v1.booking.show');
    Route::get('/positions', [PositionController::class, 'index'])->name('api.v1.positions.index');
    Route::get('/positions/{position}', [PositionController::class, 'show'])->name('api.v1.positions.show');
    Route::get('/users', [UserController::class, 'index'])->name('api.v1.users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('api.v1.users.show');

    Route::get('/areas', [AreaController::class, 'index'])->name('api.v1.areas.index');
    Route::get('/areas/{area}', [AreaController::class, 'show'])->name('api.v1.areas.show');

    Route::get('/ratings', [RatingController::class, 'index'])->name('api.v1.ratings.index');
    Route::get('/endorsements', [EndorsementController::class, 'index'])->name('api.v1.endorsements.index');
});
