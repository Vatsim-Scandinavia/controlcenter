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

Route::group(['middleware' => ['api-token:edit']], function() {
    Route::post('/bookings/create', [App\Http\Controllers\API\BookingController::class, 'store'])->name('api.booking.store');
    Route::patch('/bookings/{booking}', [App\Http\Controllers\API\BookingController::class, 'update'])->name('api.booking.update');
    Route::delete('/bookings/{booking}', [App\Http\Controllers\API\BookingController::class, 'destroy'])->name('api.booking.destroy');
});

Route::group(['middleware' => ['api-token']], function() {
    Route::get('/bookings', [App\Http\Controllers\API\BookingController::class, 'index'])->name('api.booking.index');
    Route::get('/bookings/{booking}', [App\Http\Controllers\API\BookingController::class, 'show'])->name('api.booking.show');
    
    Route::get('/roles', [App\Http\Controllers\API\RolesController::class, 'index'])->name('api.roles.index');

    Route::get('/endorsements/visiting', [App\Http\Controllers\API\VisitingController::class, 'index'])->name('api.endorsement.visiting.index');
    Route::get('/endorsements/examiner', [App\Http\Controllers\API\ExaminerController::class, 'index'])->name('api.endorsement.examiner.index');
    Route::get('/endorsements/training/solo', [App\Http\Controllers\API\TrainingController::class, 'indexSolo'])->name('api.endorsement.training.solo.index');
    Route::get('/endorsements/training/s1', [App\Http\Controllers\API\TrainingController::class, 'indexS1'])->name('api.endorsement.training.s1.index');
    Route::get('/endorsements/masc', [App\Http\Controllers\API\RatingController::class, 'index'])->name('api.endorsement.rating.index');
});