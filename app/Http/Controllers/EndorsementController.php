<?php

namespace App\Http\Controllers;

use App\Models\Endorsement;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Rating;
use App\Models\Area;
use App\Models\Position;
use Illuminate\Support\Facades\Auth;

class EndorsementController extends Controller
{
    /**
     * Display a listing of the MA/SC Endorsements
     *
     * @return \Illuminate\Http\Response
     */
    public function indexMascs()
    {

        $endorsements = Endorsement::where('type', 'MASC')->get();
        $areas = Rating::whereNull('vatsim_rating')->get();

        return view('endorsements.mascs', compact('endorsements', 'areas'));
    }

    /**
     * Display a listing of the training related endorsements such as S1 and Solo
     *
     * @return \Illuminate\Http\Response
     */
    public function indexTrainings()
    {

        $endorsements = Endorsement::where('type', 'S1')->orWhere('type', 'SOLO')->get();

        return view('endorsements.trainings', compact('endorsements'));
    }

    /**
     * Display a listing of the users with examiner endorsements
     *
     * @return \Illuminate\Http\Response
     */
    public function indexExaminers()
    {

        $endorsements = Endorsement::where('type', 'EXAMINER')->get();
        $areas = Area::all();

        return view('endorsements.examiners', compact('endorsements', 'areas'));
    }

    /**
     * Display a listing of the users with visitor endorsements
     *
     * @return \Illuminate\Http\Response
     */
    public function indexVisitors()
    {

        $endorsements = Endorsement::where('type', 'VISITING')->get();
        $areas = Area::all();

        return view('endorsements.visitors', compact('endorsements', 'areas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', SoloEndorsement::class);
        $user = Auth::user();
        $students = User::with('trainings')->has('trainings')->get();
        $positions = Position::all();
        $areas = Area::all();
        $ratingsMASC = Rating::where('vatsim_rating', null)->get();
        $ratingsGRP = Rating::where('vatsim_rating', '<=', 7)->get();

        return view('endorsements.create', compact('students', 'positions', 'areas', 'ratingsMASC', 'ratingsGRP'));
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
