<?php

namespace App\Http\Controllers;

use App\Country;
use App\User;
use App\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MentorController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(){
        $user = Auth::user();
        $trainings = $user->mentoringTrainings();
        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;
        if($user->isMentor()) return view('mentor.index', compact('trainings', 'user', 'statuses', 'types'));

        abort(403);
    }

}
