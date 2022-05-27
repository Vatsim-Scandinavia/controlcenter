<?php

use App\Http\Controllers\FrontPageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EndorsementController;
use App\Http\Controllers\OneTimeLinkController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrainingReportController;
use App\Http\Controllers\UserSearchController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\GlobalSettingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\TrainingObjectAttachmentController;
use App\Http\Controllers\TrainingExaminationController;
use App\Http\Controllers\TrainingActivityController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\SweatbookController;
use App\Http\Controllers\VatbookController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\VoteController;

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
Route::get('/', [FrontPageController::class, 'index'])->name('front');

//--------------------------------------------------------------------------
// VATSIM Authentication
//--------------------------------------------------------------------------
Route::get('/login', [LoginController::class, 'login'])->middleware('guest')->name('login');
Route::get('/validate', [LoginController::class, 'validateLogin'])->middleware('guest');
Route::get('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

//--------------------------------------------------------------------------
// Sites behind authentication
//--------------------------------------------------------------------------
Route::middleware(['auth', 'activity'])->group(function () {

    // Sidebar Navigation
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/content', [DashboardController::class, 'content'])->name('content');
    Route::get('/mentor', [DashboardController::class, 'mentor'])->name('mentor');
    Route::get('/trainings', [TrainingController::class, 'index'])->name('requests');
    Route::get('/trainings/history', [TrainingController::class, 'history'])->name('requests.history');
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/other', [UserController::class, 'indexOther'])->name('users.other');

    // Endorsements
    Route::controller(EndorsementController::class)->group(function(){
        Route::get('/endorsements/mascs', 'indexMascs')->name('endorsements.mascs');
        Route::get('/endorsements/trainings', 'indexTrainings')->name('endorsements.trainings');
        Route::get('/endorsements/examiners', 'indexExaminers')->name('endorsements.examiners');
        Route::get('/endorsements/visiting', 'indexVisitors')->name('endorsements.visiting');
        Route::get('/endorsements/create', 'create')->name('endorsements.create');
        Route::get('/endorsements/create/{id}', 'create')->name('endorsements.create.id');
        Route::post('/endorsements/store', 'store')->name('endorsements.store');
        Route::get('/endorsements/{id}/delete', 'destroy');
    });

    // Users
    Route::controller(UserController::class)->group(function(){
        Route::get('/user/{user}', 'show')->name('user.show');
        Route::patch('/user/{user}', 'update')->name('user.update');
        Route::get('/settings', 'settings')->name('user.settings');
        Route::post('/settings', 'settings_update')->name('user.settings.store');
        Route::get('/settings/extendworkmail', 'extendWorkmail')->name('user.settings.extendworkmail');
    });

    Route::get('/user/search/action', [UserSearchController::class, 'action'])->name('user.search');

    // Reports
    Route::controller(ReportController::class)->group(function(){
        Route::get('/reports/trainings', 'trainings')->name('reports.trainings');
        Route::get('/reports/training/{id}', 'trainings')->name('reports.training.area');
        Route::get('/reports/mentors', 'mentors')->name('reports.mentors');
        Route::get('/reports/access', 'access')->name('reports.access');
    });

    // Admin
    Route::get('/admin/settings', [GlobalSettingController::class, 'index'])->name('admin.settings');
    Route::post('/admin/settings', [GlobalSettingController::class, 'edit'])->name('admin.settings.store');
    Route::get('/admin/templates', [NotificationController::class, 'index'])->name('admin.templates');
    Route::get('/admin/templates/{id}', [NotificationController::class, 'index'])->name('admin.templates.area');
    Route::post('/admin/templates/update', [NotificationController::class, 'update'])->name('admin.templates.update');
    Route::get('/admin/log', [ActivityLogController::class, 'index'])->name('admin.logs');

    // Training routes
    Route::controller(TrainingController::class)->group(function(){
        Route::get('/training/apply', 'apply')->name('training.apply');
        Route::get('/training/create', 'create')->name('training.create');
        Route::get('/training/create/{id}', 'create')->name('training.create.id');
        Route::get('/training/edit/{training}', 'edit')->name('training.edit');
        Route::patch('/training/edit/{training}', 'updateRequest')->name('training.update.request');
        Route::post('/training/store', 'store')->name('training.store');
        Route::get('/training/{training}/close', 'close')->name('training.close');
        Route::patch('/training/{training}', 'updateDetails')->name('training.update.details');
        Route::get('/training/{training}', 'show')->name('training.show');

        Route::get('/training/{training}/confirm/{key}', 'confirmInterest')->name('training.confirm.interest');
    });

    // Training report routes
    Route::controller(TrainingReportController::class)->group(function(){
        Route::get('/training/report/{report}', 'edit')->name('training.report.edit');
        Route::get('/training/{training}/report/create', 'create')->name('training.report.create');
        Route::post('/training/{training}/report', 'store')->name('training.report.store');
        Route::patch('/training/report/{report}', 'update')->name('training.report.update');
        Route::get('/training/report/{report}/delete', 'destroy')->name('training.report.delete');
    });

    // Training object routes
    Route::controller(OneTimeLinkController::class)->group(function(){
        Route::get('/training/onetime/{key}', 'redirect')->name('training.onetimelink.redirect');
        Route::post('/training/{training}/onetime', 'store')->name('training.onetimelink.store');
    });

    // Training object attachment routes
    Route::controller(TrainingObjectAttachmentController::class)->group(function(){
        Route::get('/training/attachment/{attachment}', 'show')->name('training.object.attachment.show');
        Route::post('/training/{trainingObjectType}/{trainingObject}/attachment', 'store')->name('training.object.attachment.store');
        Route::delete('/training/attachment/{attachment}', 'destroy')->name('training.object.attachment.delete');
    });

    // Training examination routes
    Route::controller(TrainingExaminationController::class)->group(function(){
        Route::get('/training/examination/{examination}', 'show')->name('training.examination.show');
        Route::get('/training/{training}/examination/create', 'create')->name('training.examination.create');
        Route::post('/training/{training}/examination', 'store')->name('training.examination.store');
        Route::patch('/training/examination/{examination}', 'update')->name('training.examination.update');
        Route::get('/training/examination/{examination}/delete', 'destroy')->name('training.examination.delete');
    });

    Route::post('/training/activity/comment', [TrainingActivityController::class, 'storeComment'])->name('training.activity.comment');

    Route::controller(FileController::class)->group(function(){
        Route::get('/files/{file}', 'get')->name('file.get');
        Route::post('/files', 'store')->name('file.store');
        Route::delete('/files/{file}', 'destroy')->name('file.delete');
    });

    // Sweatbook routes
    Route::controller(SweatbookController::class)->group(function(){
        Route::get('/sweatbook', 'index')->name('sweatbook');
        Route::get('/sweatbook/{id}/delete', 'delete')->name('sweatbook.delete');
        Route::get('/sweatbook/{id}', 'show');
        Route::post('/sweatbook/store', 'store');
        Route::post('/sweatbook/update', 'update');
    });

    // Vatbook routes
    Route::controller(VatbookController::class)->group(function(){
        Route::get('/vatbook', 'index')->name('vatbook');
        Route::get('/vatbook/bulk', 'bulk')->name('vatbook.bulk');
        Route::post('/vatbook/bulk', 'storeBulk')->name('vatbook.bulk.store');
        Route::get('/vatbook/{id}/delete', 'delete')->name('vatbook.delete');
        Route::get('/vatbook/{id}', 'show');
        Route::post('/vatbook/store', 'store');
        Route::post('/vatbook/update', 'update');
    });

    // Mentor Routes
    Route::get('/mentor', [MentorController::class, 'index'])->name('mentor');

    // Vote routes
    Route::controller(VoteController::class)->group(function(){
        Route::get('/votes', 'index')->name('vote.overview');
        Route::get('/vote/create', 'create')->name('vote.create');
        Route::post('/vote/store', 'store')->name('vote.store');
        Route::patch('/vote/{id}', 'update')->name('vote.update');
        Route::get('/vote/{id}', 'show')->name('vote.show');
    });
});
