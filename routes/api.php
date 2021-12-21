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
    Route::post('/callback/bookings/create/{cid}/{position}', [App\Http\Controllers\API\VatbookController::class, 'store'])->name('api.vatbook.store');
});

