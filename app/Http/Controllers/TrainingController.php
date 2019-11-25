<?php

namespace App\Http\Controllers;

use App\Country;
use App\Rating;
use App\Training;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        // Get relevant user data
        $user = Auth::user();
        $userVatsimRating = $user->handover->rating;
        $userEndorsements = $user->ratings->where('vatsim_rating', NULL);

        // Loop through all countries, it's ratings, check if user has already passed and if not, show the appropriate ratings for current level.
        $payload = collect();

        foreach(Country::with('ratings')->get() as $country){

            $availableRatings = collect();
            foreach($country->ratings as $rating){

                $reqRating = $rating->pivot->required_vatsim_rating;
                if($userVatsimRating >= $reqRating){
                    $availableRatings->push($rating);
                }

            }

            $payload->put($country->name, $availableRatings);
        }

        /*
        $countries = Country::with('ratings')->get();
        
        $countries = $countries->filter(function($country){
            return $country->ratings->filter(function($rating){
                return $rating->pivot->required_vatsim_rating < 2;
            });
        });

        dd($countries);*/

        dd($payload);

        return view('training.apply', [
            'payload' => $payload,
        ]);
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
     * @param  \App\Training  $training
     * @return \Illuminate\Http\Response
     */
    public function show(Training $training)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Training  $training
     * @return \Illuminate\Http\Response
     */
    public function edit(Training $training)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Training  $training
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Training $training)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Training  $training
     * @return \Illuminate\Http\Response
     */
    public function destroy(Training $training)
    {
        //
    }
}
