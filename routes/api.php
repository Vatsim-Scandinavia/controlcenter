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

Route::group(['middleware' => ['client']], function() {
    Route::get('/callback/bookings', [App\Http\Controllers\API\VatbookController::class, 'index'])->name('api.vatbook.index');
    Route::get('/callback/bookings/{vatbook}', [App\Http\Controllers\API\VatbookController::class, 'show'])->name('api.vatbook.show');
    Route::post('/callback/bookings/create', [App\Http\Controllers\API\VatbookController::class, 'store'])->name('api.vatbook.store');
    Route::patch('/callback/bookings/{vatbook}', [App\Http\Controllers\API\VatbookController::class, 'update'])->name('api.vatbook.update');
    Route::delete('/callback/bookings/{vatbook}', [App\Http\Controllers\API\VatbookController::class, 'destroy'])->name('api.vatbook.destroy');
});

