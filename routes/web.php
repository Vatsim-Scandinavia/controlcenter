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
    Route::get('/users', 'UserController@index')->name('users');

    // User endorsements
    Route::get('/users/endorsements', 'UserEndorsementController@index')->name('users.endorsements');
    Route::get('/users/endorsements/create', 'UserEndorsementController@create')->name('users.endorsements.create');
    Route::get('/users/endorsements/{id}/delete', 'UserEndorsementController@delete');
    Route::post('/users/endorsements/store', 'UserEndorsementController@store');

    // Reports
    Route::get('/reports/stats', 'ReportController@stats')->name('reports.stats');
    Route::get('/reports/mentors', 'ReportController@mentors')->name('reports.mentors');
    Route::get('/reports/atc', 'ReportController@atc')->name('reports.atc');

    // Admin
    Route::get('/admin/settings', 'DashboardController@admin')->name('admin.settings');
    Route::get('/admin/content', 'DashboardController@admin')->name('admin.content');
    Route::get('/admin/templates', 'DashboardController@admin')->name('admin.templates');

    // User-specific Navigation
    Route::get('/settings', 'UserSettingController@index')->name('settings');

    // Training routes
    Route::get('/training/apply', 'TrainingController@create')->name('training.apply');
    Route::post('/training/store', 'TrainingController@store');
    Route::patch('/training/{training}', 'TrainingController@update')->name('training.update');
    Route::get('/training/{training}', 'TrainingController@show')->name('training.show');

    // Training report routes
    Route::get('/training/report/{report}', 'TrainingReportController@edit')->name('training.report.edit');
    Route::get('training/{training}/reports', 'TrainingReportController@index')->name('training.report.index');
    Route::get('/training/{training}/report/create', 'TrainingReportController@create')->name('training.report.create');
    Route::post('/training/{training}/report', 'TrainingReportController@store')->name('training.report.store');
    Route::patch('/training/report/{report}', 'TrainingReportController@update')->name('training.report.update');
    Route::delete('/training/report/{report}', 'TrainingReportController@destroy')->name('training.report.delete');

    // Training report attachment routes
    Route::get('/training/report/attachment/{attachment}', 'TrainingReportAttachmentController@show')->name('training.report.attachment.show');
    Route::post('/training/report/{report}/attachment', 'TrainingReportAttachmentController@store')->name('training.report.attachment.store');
    Route::delete('/training/report/attachment/{attachment}', 'TrainingReportAttachmentController@destroy')->name('training.report.attachment.delete');

    // Training examination routes
    Route::get('/training/examination/{examination}', 'TrainingExaminationController@show')->name('training.examination.show');
    Route::get('/training/{training}/examination/create', 'TrainingExaminationController@create')->name('training.examination.create');
    Route::post('/training/{training}/examination', 'TrainingExaminationController@store')->name('training.examination.store');
    Route::patch('/training/examination/{examination}', 'TrainingExaminationController@update')->name('training.examination.update');
    Route::delete('/training/examination/{examination}', 'TrainingExaminationController@destroy')->name('training.examination.delete');

    Route::get('/files/{file}', 'FileController@get')->name('file.get');
    Route::post('/files', 'FileController@store')->name('file.store');
    Route::delete('/files/{file}', 'FileController@destroy')->name('file.delete');

    // Sweatbox routes
    Route::get('/sweatbox', 'SweatboxController@index')->name('sweatbox');
    Route::get('/sweatbox/create', 'SweatboxController@create')->name('sweatbox.create');
    Route::get('/sweatbox/{id}/delete', 'SweatboxController@delete')->name('sweatbox.delete');
    Route::get('/sweatbox/{id}', 'SweatboxController@show');
    Route::post('/sweatbox/store', 'SweatboxController@store');
    Route::post('/sweatbox/update', 'SweatboxController@update');

    // Vatbook routes
    Route::get('/vatbook', 'VatbookController@index')->name('vatbook');
    Route::get('/vatbook/create', 'VatbookController@create')->name('vatbook.create');
    Route::get('/vatbook/{id}/delete', 'VatbookController@delete')->name('vatbook.delete');
    Route::get('/vatbook/{id}', 'VatbookController@show');
    Route::post('/vatbook/store', 'VatbookController@store');
    Route::post('/vatbook/update', 'VatbookController@update');
});
