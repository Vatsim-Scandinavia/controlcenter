<?php

namespace App\Http\Controllers;

use App\Training;
use App\TrainingReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyStudentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){
        $user = Auth::user();
        $trainings = Training::all()->sortBy('id');
        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;
        if($user->isMentor()) return view('mentor.index', compact('trainings', 'user', 'statuses', 'types'));
        
        abort(403);
    }
}
