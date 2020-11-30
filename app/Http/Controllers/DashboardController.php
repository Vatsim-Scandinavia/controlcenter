<?php

namespace App\Http\Controllers;

use App\SoloEndorsement;
use App\TrainingReport;
use App\TrainingInterest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();

        $report = TrainingReport::whereIn('training_id', $user->trainings->pluck('id'))->orderBy('created_at')->get()->last();

        $subdivision = $user->subdivision;
        if(empty($subdivision)) $subdivision = "No subdivision";

        $data = [
            'rating' => $user->ratingLong,
            'division' => $user->division,
            'subdivision' => $subdivision,
            'report' => $report
        ];

        $trainings = $user->trainings;
        $statuses = TrainingController::$statuses;

        $dueInterestRequest = TrainingInterest::whereIn('training_id', $user->trainings->pluck('id'))->where('expired', false)->get()->first();

        return view('dashboard', compact('data', 'trainings', 'statuses', 'dueInterestRequest'));
    }

    /**
     * Show the training apply view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function apply(){
        return view('trainingapply');
    }

    /**
     * Show member endorsements view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function endorsements(){

        $members = User::has('ratings')->get();

        return view('endorsements', compact('members'));
    }
}
