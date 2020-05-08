<?php

namespace App\Http\Controllers;

use App\Country;
use App\Rating;
use App\Training;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use phpDocumentor\Reflection\DocBlock\Tags\Uses;

class TrainingController extends Controller
{
    /**
     * A list of possible statuses
     *
     */
    public $statuses = [
        -3 => ["text" => "Closed by system", "color" => "danger", "icon" => "fa fa-ban", "assignableByStaff" => false],
        -2 => ["text" => "Closed by student", "color" => "danger", "icon" => "fa fa-ban", "assignableByStaff" => false],
        -1 => ["text" => "Closed by staff", "color" => "danger", "icon" => "fas fa-ban", "assignableByStaff" => true],
        0 => ["text" => "In queue", "color" => "warning", "icon" => "fas fa-hourglass", "assignableByStaff" => true],
        1 => ["text" => "In progress", "color" => "success", "icon" => "fas fa-book-open", "assignableByStaff" => true],
        2 => ["text" => "Awaiting exam", "color" => "success", "icon" => "fas fa-graduation-cap", "assignableByStaff" => true],
        3 => ["text" => "Completed", "color" => "success", "icon" => "fas fa-check", "assignableByStaff" => true]
    ];

    /**
     * A list of possible types
     *
     */
    public $types = [
        1 => ["text" => "Standard", "icon" => "fas fa-circle"],
        2 => ["text" => "Refresh", "icon" => "fas fa-sync"],
        3 => ["text" => "Transfer", "icon" => "fas fa-exchange"],
        4 => ["text" => "Fast-track", "icon" => "fas fa-fast-forward"],
        5 => ["text" => "Familiarisation", "icon" => "fas fa-compress-arrows-alt"],
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $openTrainings = Training::where('status', '>=', 0)->get();
        $closedTrainings = Training::where('status', '<', 0)->get();

        $statuses = $this->statuses;
        $types = $this->types;

        return view('training.overview', compact('openTrainings', 'closedTrainings', 'statuses', 'types'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {

        // Get relevant user data
        $user = Auth::user();
        $userVatsimRating = $user->rating;

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
     * @return Training|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validateRequest();

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

        return $training;
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Training $training
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Training $training)
    {
        $this->authorize('view', $training);

        $trainingMentors = $training->country->mentors;
        $statuses = $this->statuses;
        $types = $this->types;

        return view('training.show', compact('training', 'trainingMentors', 'statuses', 'types'));
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
     * @param Training $training
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Training $training)
    {
        $this->authorize('update', $training);

        $attributes = $this->validateRequest();
        if (key_exists('status', $attributes)) {
            $training->updateStatus($attributes['status']);
        }

        if (key_exists('mentors', $attributes)) {

            foreach ((array) $attributes['mentors'] as $mentor) {
                if (!$training->mentors->contains($mentor) && User::find($mentor) != null && User::find($mentor)->isMentor($training->country))
                    $training->mentors()->attach($mentor, ['expire_at' => now()->addMonths(12)]);
            }

            unset($attributes['mentors']);
        }

        $training->update($attributes);

        return redirect($training->path())->with("message", "Training successfully updated");
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

    /**
     * @return mixed
     */
    protected function validateRequest()
    {
        return request()->validate([
            'experience' => 'sometimes|required|integer|min:1|max:5',
            'englishOnly' => 'nullable',
            'motivation' => 'sometimes|required|min:400|max:1500',
            'comment' => 'nullable',
            'training_level' => 'sometimes|required',
            'training_country' => 'sometimes|required',
            'status' => 'sometimes|required|integer',
            'type' => 'sometimes|required|integer',
            'notes' => 'nullable',
            'mentors' => 'sometimes'
        ]);
    }
}
