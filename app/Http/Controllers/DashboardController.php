<?php

namespace App\Http\Controllers;

use App\Models\SoloEndorsement;
use App\Models\TrainingReport;
use App\Models\TrainingInterest;
use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use anlutro\LaravelSettings\Facade as Setting;
use Illuminate\Support\Facades\DB;

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
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        $report = TrainingReport::whereIn('training_id', $user->trainings->pluck('id'))->orderBy('created_at')->get()->last();

        $subdivision = $user->subdivision;
        if(empty($subdivision)) $subdivision = "No subdivision";

        $data = [
            'rating' => $user->ratingLong,
            'rating_short' => $user->ratingShort,
            'division' => $user->division,
            'subdivision' => $subdivision,
            'report' => $report
        ];

        $trainings = $user->trainings;
        $statuses = TrainingController::$statuses;

        $dueInterestRequest = TrainingInterest::whereIn('training_id', $user->trainings->pluck('id'))->where('expired', false)->get()->first();

        // If the user belongs to our subdivision, doesn't have any training requests, has S2+ rating and is marked as inactive -> show notice
        $allowedSubDivisions = explode(',', Setting::get('trainingSubDivisions'));
        $atcInactiveMessage = ((in_array($user->handover->subdivision, $allowedSubDivisions) && $allowedSubDivisions != null) && (!$user->hasActiveTrainings() && $user->rating > 2 && !$user->active) && !$user->hasRecentlyCompletedTraining());
        $completedTrainingMessage = $user->hasRecentlyCompletedTraining();

        $workmailRenewal = (isset($user->setting_workmail_expire)) ? (Carbon::parse($user->setting_workmail_expire)->diffInDays(Carbon::now(), false) > -7) : false;

        // Check if there's an active vote running to advertise
        $activeVote = Vote::where('closed', 0)->first();

        $atcHoursDB = DB::table('atc_activity')->where('user_id', $user->id)->get()->first();
        $atcHours = ($atcHoursDB == null) ? null : $atcHoursDB->atc_hours;

        return view('dashboard', compact('data', 'trainings', 'statuses', 'dueInterestRequest', 'atcInactiveMessage', 'completedTrainingMessage', 'activeVote', 'atcHours', 'workmailRenewal'));
    }

    /**
     * Show the training apply view
     *
     * @return \Illuminate\View\View
     */
    public function apply(){
        return view('trainingapply');
    }

    /**
     * Show member endorsements view
     *
     * @return \Illuminate\View\View
     */
    public function endorsements(){

        $members = User::has('ratings')->get()->sortBy('name');

        return view('endorsements', compact('members'));
    }
}
