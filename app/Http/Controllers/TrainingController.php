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
        $openTrainings = Training::where('status', '>=', 0)->get();
        $closedTrainings = Training::where('status', '<', 0)->get();

        return view('training.overview', compact('openTrainings', 'closedTrainings'));
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

        // Loop through all countries, it's ratings, check if user has already passed and if not, show the appropriate ratings for current level.
        $payload = [];

        foreach(Country::with('ratings')->get() as $country){

            $availableRatings = collect();
            foreach($country->ratings as $rating){

                $reqVatRating = $rating->pivot->required_vatsim_rating;

                // If the rating gives vatsim-rating higher than user already holds || OR if it's endorsement-rating AND user does not hold the endorsement
                if( $rating->vatsim_rating > $userVatsimRating || ($rating->vatsim_rating == NULL &&  $user->ratings->firstWhere('id', $rating->id) == null) ){

                    // If the required vatsim rating for the selection is lower or equals the level user has today, make it available
                    if($reqVatRating <= $userVatsimRating){
                        $availableRatings->push($rating);
                    }
                }
            }

            // Bundle the ratings if relevant
            $bundle = [];
            $bundleAmount = 0;
            foreach($availableRatings as $ratingIndex => $rating){

                // If the rating is an endorsement-rating, and required vatsim rating is S3 or below (to avoid bundling C1+ endorsements), bundle and remove the rating from list
                if($rating->vatsim_rating == NULL && $rating->pivot->required_vatsim_rating <= 4){
                    $bundle['id'] = empty($bundle['id']) ? $rating->id : $bundle['id'].'+'.$rating->id;
                    $bundle['name'] = empty($bundle['name']) ? $rating->name : $bundle['name'].' + '.$rating->name;
                    $bundleAmount++;

                    $availableRatings->pull($ratingIndex);
                }
            }

            // Re-add the removed ratings as a bundle
            if($bundleAmount > 0){ $availableRatings->push($bundle); }

            // Inject the data into payload
            $payload[$country->id]["name"] = $country->name;
            $payload[$country->id]["data"] = $availableRatings;
        }

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
        $data = $request->validate([
            'experience' => 'required|integer|min:1|max:5',
            'englishOnly' => 'nullable',
            'motivation' => 'required|min:400|max:1500',
            'comment' => 'nullable',
            'training_level' => 'required',
            'training_country' => 'required'
        ]);

        $ratings = explode("+", $data["training_level"]);
        $modelRatings = [];
        foreach ($ratings as $rating) {
            array_push($modelRatings, Rating::find($rating));
        }

        $training = new Training();
        $training->user_id = \Auth::id();
        $training->country_id = $data["training_country"];
        $training->notes = $data["comment"];
        $training->motivation = $data["motivation"];

        $training->english_only_training = key_exists("englishOnly", $data) ? true : false;

        $training->save();

        $training->fresh();

        $training->ratings()->saveMany($modelRatings);

        $training->save();

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
