<?php

namespace App\Http\Controllers;

use App\SoloEndorsement;
use App\TrainingReport;
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

        return view('dashboard', compact('data', 'trainings', 'statuses'));
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
     * Show solo endorsements view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function endorsements(){

        $endorsements = SoloEndorsement::all();

        return view('endorsements', compact('endorsements'));
    }
}
