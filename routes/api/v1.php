<?php

use App\Http\Controllers\API\ActivityLogController;
use App\Http\Controllers\API\AreaController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\EndorsementController;
use App\Http\Controllers\API\FeedbackController;
use App\Http\Controllers\API\PositionController;
use App\Http\Controllers\API\RatingController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\SweatbookController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\API\TrainingAttachmentController;
use App\Http\Controllers\API\TrainingController;
use App\Http\Controllers\API\TrainingExaminationController;
use App\Http\Controllers\API\TrainingReportController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VoteController;

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

    Route::post('/trainings', [TrainingController::class, 'store'])->name('api.v1.trainings.store');
    Route::patch('/trainings/{training}', [TrainingController::class, 'update'])->name('api.v1.trainings.update');
    Route::post('/trainings/{training}/close', [TrainingController::class, 'close'])->name('api.v1.trainings.close');
    Route::post('/trainings/{training}/complete', [TrainingController::class, 'complete'])->name('api.v1.trainings.complete');
    Route::post('/trainings/{training}/ratings/{rating}/complete', [TrainingController::class, 'completeRating'])->name('api.v1.trainings.ratings.complete');
    Route::post('/trainings/{training}/pretraining', [TrainingController::class, 'preTraining'])->name('api.v1.trainings.pretraining');
    Route::post('/trainings/{training}/comments', [TrainingController::class, 'comment'])->name('api.v1.trainings.comments.store');

    Route::post('/trainings/{training}/reports', [TrainingReportController::class, 'store'])->name('api.v1.trainings.reports.store');
    Route::patch('/trainings/{training}/reports/{report}', [TrainingReportController::class, 'update'])->name('api.v1.trainings.reports.update');
    Route::delete('/trainings/{training}/reports/{report}', [TrainingReportController::class, 'destroy'])->name('api.v1.trainings.reports.destroy');
    Route::post('/trainings/{training}/reports/{report}/attachments', [TrainingAttachmentController::class, 'storeForReport'])->name('api.v1.trainings.reports.attachments.store');

    Route::post('/trainings/{training}/examinations', [TrainingExaminationController::class, 'store'])->name('api.v1.trainings.examinations.store');
    Route::patch('/trainings/{training}/examinations/{examination}', [TrainingExaminationController::class, 'update'])->name('api.v1.trainings.examinations.update');
    Route::delete('/trainings/{training}/examinations/{examination}', [TrainingExaminationController::class, 'destroy'])->name('api.v1.trainings.examinations.destroy');
    Route::post('/trainings/{training}/examinations/{examination}/attachments', [TrainingAttachmentController::class, 'storeForExamination'])->name('api.v1.trainings.examinations.attachments.store');

    Route::delete('/attachments/{attachment}', [TrainingAttachmentController::class, 'destroy'])->name('api.v1.attachments.destroy');

    Route::post('/tasks', [TaskController::class, 'store'])->name('api.v1.tasks.store');
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('api.v1.tasks.complete');
    Route::post('/tasks/{task}/decline', [TaskController::class, 'decline'])->name('api.v1.tasks.decline');

    Route::post('/sweatbooks', [SweatbookController::class, 'store'])->name('api.v1.sweatbooks.store');
    Route::patch('/sweatbooks/{sweatbook}', [SweatbookController::class, 'update'])->name('api.v1.sweatbooks.update');
    Route::delete('/sweatbooks/{sweatbook}', [SweatbookController::class, 'destroy'])->name('api.v1.sweatbooks.destroy');

    Route::patch('/feedback/{feedback}', [FeedbackController::class, 'update'])->name('api.v1.feedback.update');

    Route::patch('/settings', [SettingController::class, 'update'])->name('api.v1.settings.update');

    Route::post('/votes', [VoteController::class, 'store'])->name('api.v1.votes.store');
    Route::patch('/votes/{vote}', [VoteController::class, 'update'])->name('api.v1.votes.update');
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
    Route::get('/areas/{area}/roster', [AreaController::class, 'roster'])->name('api.v1.areas.roster');
    Route::get('/areas/{area}/activity', [AreaController::class, 'activity'])->name('api.v1.areas.activity');

    Route::get('/ratings', [RatingController::class, 'index'])->name('api.v1.ratings.index');
    Route::get('/endorsements', [EndorsementController::class, 'index'])->name('api.v1.endorsements.index');

    Route::get('/trainings', [TrainingController::class, 'index'])->name('api.v1.trainings.index');
    Route::get('/trainings/{training}', [TrainingController::class, 'show'])->name('api.v1.trainings.show');
    Route::get('/trainings/{training}/activities', [TrainingController::class, 'activities'])->name('api.v1.trainings.activities');

    Route::get('/trainings/{training}/reports', [TrainingReportController::class, 'index'])->name('api.v1.trainings.reports.index');
    Route::get('/trainings/{training}/reports/{report}', [TrainingReportController::class, 'show'])->name('api.v1.trainings.reports.show');

    Route::get('/trainings/{training}/examinations', [TrainingExaminationController::class, 'index'])->name('api.v1.trainings.examinations.index');
    Route::get('/trainings/{training}/examinations/{examination}', [TrainingExaminationController::class, 'show'])->name('api.v1.trainings.examinations.show');

    Route::get('/attachments/{attachment}', [TrainingAttachmentController::class, 'show'])->name('api.v1.attachments.show');

    Route::get('/tasks', [TaskController::class, 'index'])->name('api.v1.tasks.index');

    Route::get('/sweatbooks', [SweatbookController::class, 'index'])->name('api.v1.sweatbooks.index');
    Route::get('/sweatbooks/{sweatbook}', [SweatbookController::class, 'show'])->name('api.v1.sweatbooks.show');

    Route::get('/feedback', [FeedbackController::class, 'index'])->name('api.v1.feedback.index');

    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('api.v1.activity-log.index');

    Route::get('/settings', [SettingController::class, 'index'])->name('api.v1.settings.index');

    Route::get('/votes', [VoteController::class, 'index'])->name('api.v1.votes.index');
    Route::get('/votes/{vote}', [VoteController::class, 'show'])->name('api.v1.votes.show');
});
