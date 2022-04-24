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
    Route::get('/bookings', [App\Http\Controllers\API\VatbookController::class, 'index'])->name('api.vatbook.index');
    Route::get('/bookings/{vatbook}', [App\Http\Controllers\API\VatbookController::class, 'show'])->name('api.vatbook.show');
    Route::post('/bookings/create', [App\Http\Controllers\API\VatbookController::class, 'store'])->name('api.vatbook.store');
    Route::patch('/bookings/{vatbook}', [App\Http\Controllers\API\VatbookController::class, 'update'])->name('api.vatbook.update');
    Route::delete('/bookings/{vatbook}', [App\Http\Controllers\API\VatbookController::class, 'destroy'])->name('api.vatbook.destroy');
    
    Route::get('/roles', [App\Http\Controllers\API\RolesController::class, 'index'])->name('api.roles.index');

    Route::get('/visitingcontrollers', [App\Http\Controllers\API\VisitingController::class, 'index'])->name('api.visitingcontrollers.index');
});

