<?php

namespace App\Http\Controllers;

use App\Models\Endorsement;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Rating;
use App\Models\Area;

class EndorsementController extends Controller
{
    /**
     * Display a listing of the MA/SC Endorsements
     *
     * @return \Illuminate\Http\Response
     */
    public function indexMaes()
    {

        $users = User::all();
        $endorsements = Rating::whereNull('vatsim_rating')->get();


        return view('endorsements.maes', compact('users', 'endorsements'));
    }

    /**
     * Display a listing of the training related endorsements such as S1 and Solo
     *
     * @return \Illuminate\Http\Response
     */
    public function indexTrainings()
    {

        $users = User::all();
        $endorsements = Rating::whereNull('vatsim_rating')->get();


        return view('endorsements.trainings', compact('users', 'endorsements'));
    }

    /**
     * Display a listing of the users with examiner endorsements
     *
     * @return \Illuminate\Http\Response
     */
    public function indexExaminers()
    {

        $users = User::all();
        $areas = Area::all();

        return view('endorsements.examiners', compact('users', 'areas'));
    }

    /**
     * Display a listing of the users with visitor endorsements
     *
     * @return \Illuminate\Http\Response
     */
    public function indexVisitors()
    {

        $users = User::all();
        $areas = Area::all();

        return view('endorsements.visitors', compact('users', 'areas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Endorsement  $endorsement
     * @return \Illuminate\Http\Response
     */
    public function show(Endorsement $endorsement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Endorsement  $endorsement
     * @return \Illuminate\Http\Response
     */
    public function edit(Endorsement $endorsement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Endorsement  $endorsement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Endorsement $endorsement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Endorsement  $endorsement
     * @return \Illuminate\Http\Response
     */
    public function destroy(Endorsement $endorsement)
    {
        //
    }
}
