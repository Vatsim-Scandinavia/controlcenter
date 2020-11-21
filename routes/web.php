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
Route::get('/', 'FrontPageController@index')->name('front');

// Available without login
Route::get('/users/endorsements/sup', 'SoloEndorsementController@sup')->name('users.soloendorsements.sup');

//--------------------------------------------------------------------------
// VATSIM Authentication
//--------------------------------------------------------------------------
Route::get('/login', 'Auth\LoginController@login')->middleware('guest')->name('login');
Route::get('/validate', 'Auth\LoginController@validateLogin')->middleware('guest');
Route::get('/logout', 'Auth\LoginController@logout')->middleware('auth')->name('logout');

//--------------------------------------------------------------------------
// Sites behind authentication
//--------------------------------------------------------------------------
Route::middleware('auth')->group(function () {

    // Sidebar Navigation
    Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
    Route::get('/content', 'DashboardController@content')->name('content');
    Route::get('/mentor', 'DashboardController@mentor')->name('mentor');
    Route::get('/trainings', 'TrainingController@index')->name('requests');
    Route::get('/trainings/history', 'TrainingController@history')->name('requests.history');
    Route::get('/users', 'UserController@index')->name('users');
    Route::get('endorsements', 'DashboardController@endorsements')->name('member.endorsements');

    // User endorsements
    Route::get('/users/endorsements', 'SoloEndorsementController@index')->name('users.soloendorsements');
    Route::get('/users/endorsements/create', 'SoloEndorsementController@create')->name('users.soloendorsements.create');
    Route::get('/users/endorsements/{id}/delete', 'SoloEndorsementController@delete');
    Route::post('/users/endorsements/store', 'SoloEndorsementController@store');

    Route::post('/mentor/{user}/country', 'MentorController@addCountry')->name('mentor.add.country');
    Route::post('/country/{country}/mentor', 'MentorController@addMentor')->name('country.add.mentor');
    Route::delete('/mentor/{user}/country', 'MentorController@removeCountry')->name('mentor.remove.country');
    Route::delete('/country/{country}/mentor', 'MentorController@removeMentor')->name('country.remove.mentor');

    // Users
    Route::get('/user/{user}', 'UserController@show')->name('user.show');
    Route::patch('/user/{user}', 'UserController@update')->name('user.update');
    Route::get('/settings', 'UserController@settings')->name('user.settings');
    Route::post('/settings', 'UserController@settings_update')->name('user.settings.store');

    Route::get('/user/search/action', 'UserSearchController@action')->name('user.search');

    // Reports
    Route::get('/reports/trainings', 'ReportController@trainings')->name('reports.trainings');
    Route::get('/reports/training/{id}', 'ReportController@trainings')->name('reports.training.country');
    Route::get('/reports/mentors', 'ReportController@mentors')->name('reports.mentors');
    Route::get('/reports/atc', 'ReportController@atc')->name('reports.atc');

    // Admin
    Route::get('/admin/settings', 'GlobalSettingController@index')->name('admin.settings');
    Route::post('/admin/settings', 'GlobalSettingController@edit')->name('admin.settings.store');
    Route::get('/admin/templates', 'NotificationController@index')->name('admin.templates');
    Route::get('/admin/templates/{id}', 'NotificationController@index')->name('admin.templates.country');
    Route::post('/admin/templates/update', 'NotificationController@update')->name('admin.templates.update');

    // Training routes
    Route::get('/training/apply', 'TrainingController@apply')->name('training.apply');
    Route::get('/training/create', 'TrainingController@create')->name('training.create');
    Route::post('/training/store', 'TrainingController@store')->name('training.store');
    Route::get('/training/{training}/close', 'TrainingController@close')->name('training.close');
    Route::patch('/training/{training}', 'TrainingController@update')->name('training.update');
    Route::get('/training/{training}', 'TrainingController@show')->name('training.show');

    Route::get('/training/{training}/confirm/{key}', 'TrainingController@confirmInterest')->name('training.confirm.interest');

    // Training report routes
    Route::get('/training/report/{report}', 'TrainingReportController@edit')->name('training.report.edit');
    Route::get('/training/{training}/report/create', 'TrainingReportController@create')->name('training.report.create');
    Route::post('/training/{training}/report', 'TrainingReportController@store')->name('training.report.store');
    Route::patch('/training/report/{report}', 'TrainingReportController@update')->name('training.report.update');
    Route::get('/training/report/{report}/delete', 'TrainingReportController@destroy')->name('training.report.delete');

    // Training object routes
    Route::get('/training/onetime/{key}', 'OneTimeLinkController@redirect')->name('training.onetimelink.redirect');
    Route::post('/training/{training}/onetime', 'OneTimeLinkController@store')->name('training.onetimelink.store');

    // Training object attachment routes
    Route::get('/training/attachment/{attachment}', 'TrainingObjectAttachmentController@show')->name('training.object.attachment.show');
    Route::post('/training/{trainingObjectType}/{trainingObject}/attachment', 'TrainingObjectAttachmentController@store')->name('training.object.attachment.store');
    Route::delete('/training/attachment/{attachment}', 'TrainingObjectAttachmentController@destroy')->name('training.object.attachment.delete');

    // Training examination routes
    Route::get('/training/examination/{examination}', 'TrainingExaminationController@show')->name('training.examination.show');
    Route::get('/training/{training}/examination/create', 'TrainingExaminationController@create')->name('training.examination.create');
    Route::post('/training/{training}/examination', 'TrainingExaminationController@store')->name('training.examination.store');
    Route::patch('/training/examination/{examination}', 'TrainingExaminationController@update')->name('training.examination.update');
    Route::delete('/training/examination/{examination}', 'TrainingExaminationController@destroy')->name('training.examination.delete');

    Route::get('/files/{file}', 'FileController@get')->name('file.get');
    Route::post('/files', 'FileController@store')->name('file.store');
    Route::delete('/files/{file}', 'FileController@destroy')->name('file.delete');

    // Sweatbook routes
    Route::get('/sweatbook', 'SweatbookController@index')->name('sweatbook');
    Route::get('/sweatbook/{id}/delete', 'SweatbookController@delete')->name('sweatbook.delete');
    Route::get('/sweatbook/{id}', 'SweatbookController@show');
    Route::post('/sweatbook/store', 'SweatbookController@store');
    Route::post('/sweatbook/update', 'SweatbookController@update');

    // Vatbook routes
    Route::get('/vatbook', 'VatbookController@index')->name('vatbook');
    Route::get('/vatbook/{id}/delete', 'VatbookController@delete')->name('vatbook.delete');
    Route::get('/vatbook/{id}', 'VatbookController@show');
    Route::post('/vatbook/store', 'VatbookController@store');
    Route::post('/vatbook/update', 'VatbookController@update');

    // Mentor Routes
    Route::get('/mentor', 'MentorController@index')->name('mentor');

    // Vote routes
    Route::get('/votes', 'VoteController@index')->name('vote.overview');
    Route::get('/vote/create', 'VoteController@create')->name('vote.create');
    Route::post('/vote/store', 'VoteController@store')->name('vote.store');
    Route::patch('/vote/{id}', 'VoteController@update')->name('vote.update');
    Route::get('/vote/{id}', 'VoteController@show')->name('vote.show');
});
