<?php

namespace App\Http\Controllers;

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

        $report = TrainingReport::whereIn('training_id', $user->trainings->pluck('id')->toArray())->orderBy('created_at')->get()->last();

        $data = [
            'rating' => $user->ratingLong,
            'report' => $report
        ];

        $trainings = $user->trainings;

        return view('dashboard', compact('data', 'trainings'));
    }

    /**
     * Show the training apply view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function apply(){
        return view('trainingapply');
    }
}
