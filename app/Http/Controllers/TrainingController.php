<?php

namespace App\Http\Controllers;

use App\Country;
use App\Notifications\TrainingCreatedNotification;
use App\Notifications\TrainingClosedNotification;
use App\Notifications\TrainingMentorNotification;
use App\Rating;
use App\Training;
use App\TrainingExamination;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use phpDocumentor\Reflection\DocBlock\Tags\Uses;

class TrainingController extends Controller
{
    /**
     * A list of possible statuses
     *
     */
    public static $statuses = [
        -4 => ["text" => "Closed by system", "color" => "danger", "icon" => "fa fa-ban", "assignableByStaff" => false],
        -3 => ["text" => "Closed by student", "color" => "danger", "icon" => "fa fa-ban", "assignableByStaff" => false],
        -2 => ["text" => "Closed by staff", "color" => "danger", "icon" => "fas fa-ban", "assignableByStaff" => true],
        -1 => ["text" => "Completed", "color" => "success", "icon" => "fas fa-check", "assignableByStaff" => true],
        0 => ["text" => "In queue", "color" => "warning", "icon" => "fas fa-hourglass", "assignableByStaff" => true],
        1 => ["text" => "Pre-training", "color" => "success", "icon" => "fas fa-book-open", "assignableByStaff" => true],
        2 => ["text" => "Active training", "color" => "success", "icon" => "fas fa-book-open", "assignableByStaff" => true],
        3 => ["text" => "Awaiting exam", "color" => "success", "icon" => "fas fa-graduation-cap", "assignableByStaff" => true],
    ];

    /**
     * A list of possible types
     *
     */
    public static $types = [
        1 => ["text" => "Standard", "icon" => "fas fa-circle"],
        2 => ["text" => "Refresh", "icon" => "fas fa-sync"],
        3 => ["text" => "Transfer", "icon" => "fas fa-exchange"],
        4 => ["text" => "Fast-track", "icon" => "fas fa-fast-forward"],
        5 => ["text" => "Familiarisation", "icon" => "fas fa-compress-arrows-alt"],
    ];

    /**
     * A list of possible experiences
     *
     */
    public static $experiences = [
        1 => ["text" => "New to VATSIM"],
        2 => ["text" => "Experienced on VATSIM"],
        3 => ["text" => "Real world pilot"],
        4 => ["text" => "Real world ATC"],
        5 => ["text" => "Holding ATC rating from other vACC"],
        6 => ["text" => "Holding ATC rating from other virtual network"],
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \App\Exceptions\PolicyMethodMissingException
     * @throws \App\Exceptions\PolicyMissingException
     */
    public function index()
    {

        $openTrainings = Auth::user()->viewableModels(\App\Training::class, [['status', '>=', 0]])->sortByDesc('status');

        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;

        return view('training.index', compact('openTrainings', 'statuses', 'types'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \App\Exceptions\PolicyMethodMissingException
     * @throws \App\Exceptions\PolicyMissingException
     */
    public function history()
    {

        $closedTrainings = Auth::user()->viewableModels(\App\Training::class, [['status', '<', 0]])->sortBy('id');

        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;

        return view('training.history', compact('closedTrainings', 'statuses', 'types'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function apply()
    {
        $this->authorize('apply', Training::class);

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

    public function create(Request $request)
    {
        $this->authorize('create', Training::class);

        $students = User::all();
        $ratings = Country::with('ratings')->get()->toArray();

        return view('training.create', compact('students', 'ratings'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return Training|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validateRequest();

        if (isset($data['user_id']) && User::find($data['user_id']) == null)
            return response(['message' => 'The given CID cannot be found in the application database. Please check the user has logged in before.'], 400);

        // Training_level comes from the application ratings comes from the manual creation, we need to seperate those.
        if(isset($data['training_level'])){
            $ratings = Rating::find(explode("+", $data["training_level"]));
        } elseif(isset($data['ratings'])){
            $ratings = Rating::find($data["ratings"]);
        } else {
            return redirect()->back()->withErrors('One or more ratings need to be selected to create training request.');
        }

        $training = Training::create([
            'user_id' => isset($data['user_id']) ? $data['user_id'] : \Auth::id(),
            'country_id' => $data['training_country'],
            'notes' => isset($data['comment']) ? 'Comment from application: '.$data['comment'] : '',
            'motivation' => isset($data['motivation']) ? $data['motivation'] : '',
            'experience' => isset($data['experience']) ? $data['experience'] : null,
            'english_only_training' => key_exists("englishOnly", $data) ? true : false
        ]);

        if($ratings->count() > 1){
            $training->ratings()->saveMany($ratings);
        } else {
            $training->ratings()->save($ratings->first());
        }

        ActivityLogController::warning('Created training request '.$training->id.' for '.$training->user_id.' with rating: '.$ratings->pluck('name').' in '.Country::find($training->country_id)->name);

        // Send confimration mail
        $training->user->notify(new TrainingCreatedNotification($training));

        if ($request->expectsJson()) {
            return $training;
        }

        return redirect()->intended(route('dashboard'))->withSuccess('Training successfully added');
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

        $examinations = TrainingExamination::where('training_id', $training->id)->get();

        $trainingMentors = $training->country->mentors;
        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;
        $experiences = TrainingController::$experiences;

        return view('training.show', compact('training', 'examinations', 'trainingMentors', 'statuses', 'types', 'experiences'));
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
        $oldStatus = $training->fresh()->status;

        $attributes = $this->validateRequest();
        if (key_exists('status', $attributes)) {
            $training->updateStatus($attributes['status']);
        }

        if (key_exists('mentors', $attributes)) {

            $notifyOfNewMentor = false;

            foreach ((array) $attributes['mentors'] as $mentor) {
                if (!$training->mentors->contains($mentor) && User::find($mentor) != null && User::find($mentor)->isMentor($training->country)) {
                    $training->mentors()->attach($mentor, ['expire_at' => now()->addMonths(12)]);

                    // Notify student of their new mentor
                    $notifyOfNewMentor = true;
                }
            }

            foreach ($training->mentors as $mentor) {
                if (!in_array($mentor->id, (array) $attributes['mentors'])) {
                    $training->mentors()->detach($mentor);
                }
            }

            // Notify student of their new mentor. We put this here so detached mentors ain't included.
            if($notifyOfNewMentor) $training->user->notify(new TrainingMentorNotification($training));

            unset($attributes['mentors']);
        } else if (Auth::user()->isModerator()) { // XXX This is really hack since we don't send this attribute when mentors submit
            // Detach all if no passed key, as that means the list is empty
            $training->mentors()->detach();
        }

        // Update paused time for queue estimation
        isset($attributes['paused_at']) ? $attributes["paused_at"] = Carbon::now() : $attributes["paused_at"] = NULL;
        if(!isset($attributes['paused_at']) && isset($training->paused_at)){
            $training->paused_length = $training->paused_length + Carbon::create($training->paused_at)->diffInSeconds(Carbon::now());
            $training->update(['paused_length' => $training->paused_length]);
        }

        // Update the training
        $training->update($attributes);

        ActivityLogController::warning('Updated training request '.$training->id.
        '. Status: '.TrainingController::$statuses[$training->status]["text"].
        ', training type: '.$training->type.
        ', mentor: '.$training->mentors->pluck('name'));

        // Send e-mail and store endorsements rating (non-GRP ones), if it's a new status and it goes from active to closed
        if((int)$training->status != $oldStatus){
            if((int)$training->status < 0){

                // Detach all mentors
                $training->mentors()->detach();

                // If the training was completed and double checked with a passed exam result, store the relevant endorsements
                if((int)$training->status == -1 && TrainingExamination::where('result', '=', 'PASSED')->where('training_id', $training->id)->exists()){
                    foreach($training->ratings as $rating){
                        if($rating->vatsim_rating == null){
                            $training->user->ratings()->attach($rating->id);
                        }
                    }
                }

                $training->user->notify(new TrainingClosedNotification($training, (int)$training->status));
                return redirect($training->path())->withSuccess("Training successfully closed. E-mail confirmation sent to student.");
            }
        }

        return redirect($training->path())->withSuccess("Training successfully updated");
    }

    /**
     * Confirm the continued interest in the training
     *
     * @param Training $training
     * @param string $key
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function confirmInterest(Training $training, string $key)
    {

        $notification = DB::table(Training::CONTINUED_INTEREST_NOTIFICATION_LOG_TABLE)
                            ->where('training_id', '=', $training->id)
                            ->get()
                            ->sortBy('created_at')
                            ->last();

        if ($notification->key != $key || Auth::id() != $training->user->id || $training->id != $notification->training_id) {
            return response('', 400);
        }

        DB::table(Training::CONTINUED_INTEREST_NOTIFICATION_LOG_TABLE)
                ->where('notification_id', $notification->notification_id)
                ->update([
                    'confirmed_at' => now(),
                    'updated_at' => now()
                ]);

        ActivityLogController::info('Training interest confirmed.');
        return redirect()->to($training->path())->withSuccess('Interest successfully confirmed');

    }

    /**
     * @return mixed
     */
    protected function validateRequest()
    {
        return request()->validate([
            'experience' => 'sometimes|required|integer|min:1|max:5',
            'englishOnly' => 'nullable',
            'paused_at' => 'sometimes',
            'motivation' => 'sometimes|required|min:250|max:1500',
            'user_id' => 'sometimes|required|integer',
            'comment' => 'nullable',
            'training_level' => 'sometimes|required',
            'ratings' => 'sometimes|required',
            'training_country' => 'sometimes|required',
            'status' => 'sometimes|required|integer',
            'type' => 'sometimes|required|integer',
            'notes' => 'nullable',
            'mentors' => 'sometimes',
        ]);
    }
}
