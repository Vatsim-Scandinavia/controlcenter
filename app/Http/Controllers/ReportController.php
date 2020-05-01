<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class ReportController extends Controller
{
    /**
     * Show the training apply view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function trainings(){
        return view('reports.trainings');
    }

    /**
     * Show the training apply view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function mentors(){
        return view('reports.mentors');
    }

    /**
     * Show the training apply view
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function atc(){

        $controllers = User::all();

        return view('reports.atc', compact('controllers'));
    }
}
