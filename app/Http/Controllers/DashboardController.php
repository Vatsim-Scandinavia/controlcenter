<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App;
use App\Helpers\VatsimRating;
use App\Models\TrainingInterest;
use App\Models\TrainingReport;
use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller for the dashboard
 */
class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return View
     */
    public function index()
    {
        $user = Auth::user();

        $report = TrainingReport::whereIn('training_id', $user->trainings->pluck('id'))->latest()->first();

        $subdivision = $user->subdivision;
        if (empty($subdivision)) {
            $subdivision = 'No subdivision';
        }

        $data = [
            'rating' => $user->rating_long,
            'rating_short' => $user->rating_short,
            'division' => $user->division,
            'subdivision' => $subdivision,
            'report' => $report,
        ];

        $trainings = $user->trainings;
        $types = TrainingController::$types;

        $dueInterestRequest = TrainingInterest::whereIn('training_id', $user->trainings->pluck('id'))->where('expired', false)->first();

        // If the user belongs to our subdivision, doesn't have any training requests, has S2+ rating and is marked as inactive -> show notice
        $allowedSubDivisions = explode(',', Setting::get('trainingSubDivisions'));
        $atcInactiveMessage = (
            (
                (config('app.mode') == 'subdivision' && in_array($user->subdivision, $allowedSubDivisions) && $allowedSubDivisions != null)
                || (config('app.mode') == 'division' && $user->division == config('app.owner_code'))
            )
            && ! $user->hasActiveTrainings(true) && $user->rating->isGreaterThan(VatsimRating::OBS) && ! $user->isAtcActive() && ! $user->hasRecentlyCompletedTraining()
        );
        $completedTrainingMessage = $user->hasRecentlyCompletedTraining();

        $workmailRenewal = (isset($user->setting_workmail_expire)) ? (Carbon::parse($user->setting_workmail_expire)->diffInDays(Carbon::now(), false) > -7) : false;

        // Check if there's an active vote running to advertise
        $activeVote = Vote::where('closed', 0)->first();

        $atcHours = ($user->atcActivity->count()) ? $user->atcActivity->sum('hours') : null;

        $studentTrainings = \Auth::user()->mentoringTrainings();

        $cronJobError = (($user->hasPermission('system.health.view') && App::environment('production')) && (Carbon::parse(Setting::get('_lastCronRun', '2000-01-01')) <= Carbon::now()->subMinutes(5)));

        $oudatedVersionWarning = $user->hasPermission('system.health.view') && Setting::get('_updateAvailable');

        return view('dashboard', compact('data', 'trainings', 'types', 'dueInterestRequest', 'atcInactiveMessage', 'completedTrainingMessage', 'activeVote', 'atcHours', 'workmailRenewal', 'studentTrainings', 'cronJobError', 'oudatedVersionWarning'));
    }

    /**
     * Show the training apply view
     *
     * @return View
     */
    public function apply()
    {
        return view('trainingapply');
    }

    /**
     * Show member endorsements view
     *
     * @return View
     */
    public function endorsements()
    {
        $members = User::has('ratings')->orderBy('first_name')->orderBy('last_name')->get();

        return view('endorsements', compact('members'));
    }
}
