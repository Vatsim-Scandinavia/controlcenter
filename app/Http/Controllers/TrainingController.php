<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Notifications\TrainingCreatedNotification;
use App\Notifications\TrainingClosedNotification;
use App\Notifications\TrainingMentorNotification;
use App\Notifications\TrainingPreStatusNotification;
use App\Models\Rating;
use App\Models\Training;
use App\Models\TrainingReport;
use App\Models\TrainingExamination;
use App\Models\TrainingInterest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use phpDocumentor\Reflection\DocBlock\Tags\Uses;

/**
 * Controller for all trainings
 */
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
        1 => ["text" => "Pre-training", "color" => "info", "icon" => "fas fa-book-open", "assignableByStaff" => true],
        2 => ["text" => "Active training", "color" => "success", "icon" => "fas fa-book-open", "assignableByStaff" => true],
        3 => ["text" => "Awaiting exam", "color" => "warning", "icon" => "fas fa-graduation-cap", "assignableByStaff" => true],
    ];

    /**
     * A list of possible types
     *
     */
    public static $types = [
        1 => ["text" => "Standard", "icon" => "fas fa-circle"],
        2 => ["text" => "Refresh", "icon" => "fas fa-sync"],
        3 => ["text" => "Transfer", "icon" => "fas fa-exchange-alt"],
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {

        $this->authorize('viewActiveRequests', Training::class);

        $openTrainings = Auth::user()->viewableModels(\App\Models\Training::class, [['status', '>=', 0]])->sort(function($a, $b) {
            if ($a->status == $b->status) {
                return $a->created_at->timestamp - $b->created_at->timestamp;
            }
        
            return $b->status - $a->status;
        });

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function history()
    {

        $this->authorize('viewHistoricRequests', Training::class);

        $closedTrainings = Auth::user()->viewableModels(\App\Models\Training::class, [['status', '<', 0]])->sort(function($a, $b) {
            if ($a->status == $b->status) {
                return $b->created_at->timestamp - $a->created_at->timestamp;
            }
        
            return $b->status - $a->status;
        });

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

        // Loop through all areas, it's ratings, check if user has already passed and if not, show the appropriate ratings for current level.
        $payload = [];

        foreach(Area::with('ratings')->get() as $area){

            $availableRatings = collect();
            foreach($area->ratings as $rating){

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

                // If the rating is a MAE rating, or it's a VATSIM-rating allowed to bundle with MAEs. AND if the required vatsim rating to apply for the rating is S3 or below (so we don't bundle C1+ MAEs)
                if(($rating->vatsim_rating == NULL || $rating->pivot->allow_mae_bundling) && $rating->pivot->required_vatsim_rating <= 4){
                    $bundle['id'] = empty($bundle['id']) ? $rating->id : $bundle['id'].'+'.$rating->id;
                    $bundle['name'] = empty($bundle['name']) ? $rating->name : $bundle['name'].' + '.$rating->name;
                    $bundleAmount++;

                    $availableRatings->pull($ratingIndex);
                }
            }

            // Re-add the removed ratings as a bundle
            if($bundleAmount > 0){ $availableRatings->push($bundle); }

            // Inject the data into payload
            $payload[$area->id]["name"] = $area->name;
            $payload[$area->id]["data"] = $availableRatings;
        }

        return view('training.apply', [
            'payload' => $payload,
        ]);
    }


    /**
     * Create a new instance of the resourcebundle_count
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $this->authorize('create', Training::class);

        $students = User::all();
        $ratings = Area::with('ratings')->get()->toArray();
        $types = TrainingController::$types;

        return view('training.create', compact('students', 'ratings', 'types'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return Training|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $data = $this->validateUpdateDetails();
        $this->authorize('store', [Training::class, $data]);

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
            'area_id' => $data['training_area'],
            'notes' => isset($data['comment']) ? 'Comment from application: '.$data['comment'] : '',
            'motivation' => isset($data['motivation']) ? $data['motivation'] : '',
            'experience' => isset($data['experience']) ? $data['experience'] : null,
            'english_only_training' => key_exists("englishOnly", $data) ? true : false,
            'type' => isset($data['type']) ? $data['type'] : 1
        ]);

        if($ratings->count() > 1){
            $training->ratings()->saveMany($ratings);
        } else {
            $training->ratings()->save($ratings->first());
        }

        ActivityLogController::info('TRAINING', 'Created training request '.$training->id.' for CID '.$training->user_id.' ― Ratings: '.$ratings->pluck('name').' in '.Area::find($training->area_id)->name);

        // Send confimration mail
        $training->user->notify(new TrainingCreatedNotification($training));

        if ($request->expectsJson()) {
            return $training;
        }

        return redirect()->intended(route('training.show', $training->id))->withSuccess('Training successfully created!');
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Training $training
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Training $training)
    {
        $this->authorize('view', $training);

        $examinations = TrainingExamination::where('training_id', $training->id)->get();
        $reports = TrainingReport::where('training_id', $training->id)->get();

        $reportsAndExams = collect($reports)->merge($examinations);
        $reportsAndExams = $reportsAndExams->sort(function ($a, $b) {

            // Define the correct date to sort by model type is report or exam
            is_a($a, '\App\Models\TrainingReport') ? $aSort = Carbon::parse($a->report_date) : $aSort = Carbon::parse($a->examination_date);
            is_a($b, '\App\Models\TrainingReport') ? $bSort = Carbon::parse($b->report_date) : $bSort = Carbon::parse($b->examination_date);

            // Sorting algorithm
            if ($aSort == $bSort) {
                return ($a->id > $b->id) ? -1 : 1;
            }
            return ($aSort > $bSort) ? -1 : 1;
        });

        $trainingMentors = $training->area->mentors;
        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;
        $experiences = TrainingController::$experiences;

        $trainingInterests = TrainingInterest::where('training_id', $training->id)->orderBy('created_at')->get();
        $activeTrainingInterest = TrainingInterest::where('training_id', $training->id)->where('expired', false)->get()->count();     

        return view('training.show', compact('training', 'reportsAndExams', 'trainingMentors', 'statuses', 'types', 'experiences', 'trainingInterests', 'activeTrainingInterest'));
    }

    /**
     * Create a new instance of the resourcebundle_count
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function edit(Training $training)
    {
        $this->authorize('edit', [Training::class, $training]);

        $ratings = Area::where('id', $training->area_id)->get()->first()->ratings;
        $types = TrainingController::$types;

        return view('training.edit', compact('training', 'ratings', 'types'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Training $training
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateRequest(Training $training)
    {
        $this->authorize('update', $training);
        $attributes = $this->validateUpdateEdit();

        // Lets remeber what it was before for showing the change in logs
        $preChangeRatings = $training->ratings;
        $preChangeType = $training->type;

        // Detach all ratings connceed to training to save the new (or same) ones.
        $training->ratings()->detach();

        // Check if ratings has been provided at all
        if(isset($attributes['ratings'])){
            $ratings = Rating::find($attributes["ratings"]);
        } else {
            return redirect()->back()->withErrors('One or more ratings need to be selected to update training request.');
        }

        // Save the ratings
        if($ratings->count() > 1){
            $training->ratings()->saveMany($ratings);
        } else {
            $training->ratings()->save($ratings->first());
        }

        // Save the rest
        $training->type = $attributes['type'];
        $training->english_only_training = key_exists("englishOnly", $attributes) ? true : false;

        $training->save();

        // Log the action
        ActivityLogController::warning('TRAINING', 'Updated training request '.$training->id.
        ' ― Old Ratings: '.$preChangeRatings->pluck('name').
        ' ― New Ratings: '.$ratings->pluck('name').
        ' ― Old Training type: '.TrainingController::$types[$preChangeType]['text'].
        ' ― New Training type: '.TrainingController::$types[$training->type]['text'].
        ' ― English only: '. ($training->english_only_training ? 'true' : 'false'));

        return redirect($training->path())->withSuccess("Training successfully updated");
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Training $training
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateDetails(Training $training)
    {
        $this->authorize('update', $training);
        $oldStatus = $training->fresh()->status;

        $attributes = $this->validateUpdateDetails();
        if (key_exists('status', $attributes)) {
            $training->updateStatus($attributes['status']);
        }

        if (key_exists('mentors', $attributes)) {

            $notifyOfNewMentor = false;

            foreach ((array) $attributes['mentors'] as $mentor) {
                if (!$training->mentors->contains($mentor) && User::find($mentor) != null && User::find($mentor)->isMentorOrAbove($training->area)) {
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

        ActivityLogController::warning('TRAINING', 'Updated training details '.$training->id.
        ' ― Old Status: '.TrainingController::$statuses[$oldStatus]["text"].
        ' ― New Status: '.TrainingController::$statuses[$training->status]["text"].
        ' ― Mentor: '.$training->mentors->pluck('name'));

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

                $training->user->notify(new TrainingClosedNotification($training, (int)$training->status, $training->closed_reason));
                return redirect($training->path())->withSuccess("Training successfully closed. E-mail confirmation sent to student.");
            }

            if((int)$training->status == 1){
                $training->user->notify(new TrainingPreStatusNotification($training));
                return redirect($training->path())->withSuccess("Training successfully updated. E-mail confirmation of pre-training sent to student.");
            }
            
        }

        return redirect($training->path())->withSuccess("Training successfully updated");
    }

    /**
     * Close the specified resource in storage.
     *
     * @param Training $training
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @param string
     */
    public function close(Training $training)
    {
        $this->authorize('close', $training);
        ActivityLogController::warning('TRAINING', 'Student closed training request '.$training->id.
        ' ― Status: '.TrainingController::$statuses[$training->status]["text"].
        ' ― Training type: '.TrainingController::$types[$training->type]["text"]);
        $training->mentors()->detach();
        $training->updateStatus(-3);
        $training->user->notify(new TrainingClosedNotification($training, (int)$training->status));
        return redirect($training->path())->withSuccess("Training successfully closed.");
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

        $interest = TrainingInterest::where('training_id', $training->id)->where('expired', false)->orderBy('created_at')->get()->last();

        if(isset($interest)){
            if ($interest->key != $key || Auth::id() != $training->user->id || $training->id != $interest->training_id) {
                return abort(403);
            }
    
            $interest->confirmed_at = now();
            $interest->updated_at = now();
            $interest->expired = true;
            $interest->save();
    
            ActivityLogController::info('TRAINING', 'Training interest confirmed.');
            return redirect()->to($training->path())->withSuccess('Interest successfully confirmed');
        }

        return redirect()->to($training->path())->withErrors('This training interest request link has expired. Please contact staff if this is an error.');

    }

    /**
     * @return mixed
     */
    protected function validateUpdateDetails()
    {
        return request()->validate([
            'experience' => 'sometimes|required|integer|min:1|max:6',
            'englishOnly' => 'nullable',
            'paused_at' => 'sometimes',
            'motivation' => 'sometimes|required|min:250|max:1500',
            'user_id' => 'sometimes|required|integer',
            'comment' => 'nullable',
            'training_level' => 'sometimes|required',
            'ratings' => 'sometimes|required',
            'training_area' => 'sometimes|required',
            'status' => 'sometimes|required|integer',
            'type' => 'sometimes|integer',
            'notes' => 'nullable',
            'mentors' => 'sometimes',
            'closed_reason' => 'sometimes|max:50',
        ]);
    }

    /**
     * @return mixed
     */
    protected function validateUpdateEdit()
    {
        return request()->validate([
            'type' => 'sometimes|required|integer',
            'englishOnly' => 'nullable',
            'ratings' => 'sometimes|required',
        ]);
    }
}
