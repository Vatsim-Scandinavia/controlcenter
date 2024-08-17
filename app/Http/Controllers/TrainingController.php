<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App;
use App\Facades\DivisionApi;
use App\Helpers\TrainingStatus;
use App\Helpers\VatsimRating;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\Rating;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\TrainingInterest;
use App\Models\TrainingReport;
use App\Models\User;
use App\Notifications\TrainingClosedNotification;
use App\Notifications\TrainingCreatedNotification;
use App\Notifications\TrainingMentorNotification;
use App\Notifications\TrainingPreStatusNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for all trainings
 */
class TrainingController extends Controller
{
    /**
     * A list of possible statuses
     */
    public static $statuses = [
        -4 => ['text' => 'Closed by system', 'color' => 'danger', 'icon' => 'fa fa-ban', 'assignableByStaff' => false],
        -3 => ['text' => 'Closed by student', 'color' => 'danger', 'icon' => 'fa fa-ban', 'assignableByStaff' => false],
        -2 => ['text' => 'Closed by staff', 'color' => 'danger', 'icon' => 'fas fa-ban', 'assignableByStaff' => true],
        -1 => ['text' => 'Completed', 'color' => 'success', 'icon' => 'fas fa-check', 'assignableByStaff' => true],
        0 => ['text' => 'In queue', 'color' => 'warning', 'icon' => 'fas fa-hourglass', 'assignableByStaff' => true],
        1 => ['text' => 'Pre-training', 'color' => 'info', 'icon' => 'fas fa-book-open', 'assignableByStaff' => true],
        2 => ['text' => 'Active training', 'color' => 'success', 'icon' => 'fas fa-book-open', 'assignableByStaff' => true],
        3 => ['text' => 'Awaiting exam', 'color' => 'warning', 'icon' => 'fas fa-graduation-cap', 'assignableByStaff' => true],
    ];

    /**
     * A list of possible types
     */
    public static $types = [
        1 => ['text' => 'Standard', 'icon' => 'fas fa-circle'],
        2 => ['text' => 'Refresh', 'icon' => 'fas fa-sync'],
        3 => ['text' => 'Transfer', 'icon' => 'fas fa-exchange-alt'],
        4 => ['text' => 'Fast-track', 'icon' => 'fas fa-fast-forward'],
        5 => ['text' => 'Familiarisation', 'icon' => 'fas fa-compress-arrows-alt'],
    ];

    /**
     * A list of possible experiences
     */
    public static $experiences = [
        1 => ['text' => 'New to VATSIM'],
        2 => ['text' => 'Experienced on VATSIM'],
        3 => ['text' => 'Real world pilot'],
        4 => ['text' => 'Real world ATC'],
        5 => ['text' => 'Holding ATC rating from other vACC'],
        6 => ['text' => 'Holding ATC rating from other virtual network'],
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @throws \App\Exceptions\PolicyMethodMissingException
     * @throws \App\Exceptions\PolicyMissingException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('viewActiveRequests', Training::class);

        $openTrainings = Auth::user()->viewableModels(\App\Models\Training::class, [['status', '>=', 0]], ['area', 'ratings', 'activities', 'mentors', 'user', 'user.groups', 'user.groups', 'user.atcActivity'])->sort(function ($a, $b) {
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
     *
     * @throws \App\Exceptions\PolicyMethodMissingException
     * @throws \App\Exceptions\PolicyMissingException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function history()
    {
        $this->authorize('viewHistoricRequests', Training::class);

        $closedTrainings = Auth::user()->viewableModels(\App\Models\Training::class, [['status', '<', 0]], ['area', 'reports', 'ratings', 'activities', 'mentors', 'user', 'user.groups', 'user.groups'])->sortByDesc('closed_at');

        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;

        return view('training.history', compact('closedTrainings', 'statuses', 'types'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
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

        foreach (Area::with('ratings')->get() as $area) {
            $availableRatings = collect();
            foreach ($area->ratings as $rating) {
                $reqVatRating = $rating->pivot->required_vatsim_rating;

                // If the rating gives vatsim-rating higher than user already holds || OR if it's endorsement-rating AND user does not hold the endorsement
                if ($rating->vatsim_rating > $userVatsimRating || ($rating->vatsim_rating == null && $user->hasEndorsementRating($rating) == false)) {
                    // If the required vatsim rating for the selection is lower or equals the level user has today, make it available
                    if ($reqVatRating <= $userVatsimRating) {
                        $rating->hour_requirement = $rating->pivot->hour_requirement;
                        $availableRatings->push($rating);
                    }
                }
            }

            // Bundle the ratings if relevant
            $bundle = [];
            $bundleAmount = 0;

            foreach ($availableRatings as $ratingIndex => $rating) {
                // If the rating is a MAE rating, or it's a VATSIM-rating allowed to bundle with MAEs. AND if the required vatsim rating to apply for the rating is S3 or below (so we don't bundle C1+ MAEs)
                if ($rating->pivot->allow_bundling && $rating->pivot->required_vatsim_rating <= 4) {
                    $bundle['id'] = empty($bundle['id']) ? $rating->id : $bundle['id'] . '+' . $rating->id;
                    $bundle['name'] = empty($bundle['name']) ? $rating->name : $bundle['name'] . ' + ' . $rating->name;
                    if (! isset($bundle['hour_requirement']) || $rating->hour_requirement > $bundle['hour_requirement']) {
                        $bundle['hour_requirement'] = $rating->hour_requirement;
                    }

                    $bundleAmount++;
                    $availableRatings->pull($ratingIndex);
                }
            }

            // Re-add the removed ratings as a bundle
            if ($bundleAmount > 0) {
                $availableRatings->push($bundle);
            }

            // Inject the data into payload
            $payload[$area->id]['name'] = $area->name;
            $payload[$area->id]['data'] = $availableRatings;
            $payload[$area->id]['waitingTime'] = $area->waiting_time ?? 'unknown';
            $payload[$area->id]['atcActive'] = ($user->atcActivity->firstWhere('area_id', $area->id) && $user->atcActivity->firstWhere('area_id', $area->id)->atc_active) ? true : false;
        }

        // Fetch user's ATC hours
        $vatsimStats = [];
        $client = new \GuzzleHttp\Client();
        if (App::environment('production')) {
            $res = $client->request('GET', 'https://api.vatsim.net/v2/members/' . $user->id . '/stats');
        } else {
            $res = $client->request('GET', 'https://api.vatsim.net/v2/members/819096/stats');
        }

        if ($res->getStatusCode() == 200) {
            $vatsimStats = json_decode($res->getBody(), true);

            if (isset($vatsimStats[strtolower($user->rating_short)])) {
                $vatsimStats = $vatsimStats[strtolower($user->rating_short)];
            } else {
                $vatsimStats = 0;
            }
        } else {
            return redirect()->back()->withErrors('We were unable to load the application for you due to missing data from VATSIM. Please try again later.');
        }

        // Is activity in area required to apply for training?
        $atcActiveRequired = $user->rating >= VatsimRating::S1->value && Setting::get('atcActivityBasedOnTotalHours') == false;

        // Return
        return view('training.apply', [
            'payload' => $payload,
            'atc_hours' => $vatsimStats,
            'atcActiveRequired' => ($atcActiveRequired) ? 1 : 0,
            'motivation_required' => ($userVatsimRating <= 2) ? 1 : 0,
        ]);
    }

    /**
     * Create a new instance of the resourcebundle_count
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request, $prefillUserId = null)
    {
        $this->authorize('create', Training::class);

        $students = User::all();
        $types = TrainingController::$types;

        // Fetch all ratings and add C3 to all areas
        $ratings = Area::with('ratings')->get()->each(function ($area) {
            $area->ratings->push(Rating::where('name', 'C3')->first());
        })->sortBy('name')->toArray();

        return view('training.create', compact('students', 'ratings', 'types', 'prefillUserId'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Training|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validateUpdateDetails();
        $this->authorize('store', [Training::class, $data]);

        if (isset($data['user_id']) && User::find($data['user_id']) == null) {
            return response(['message' => 'The given CID cannot be found in the application database. Please check the user has logged in before.'], 400);
        }

        // Only allow one training request at a time
        if (isset($data['user_id'])) {
            if (User::find($data['user_id'])->hasActiveTrainings(true)) {
                return redirect()->back()->withErrors('The user already has an active training request.');
            }
        } elseif (Auth::user()->hasActiveTrainings(true)) {
            return redirect()->back()->withErrors('You already have an active training request.');
        }

        // Training_level comes from the application, ratings comes from the manual creation, we need to seperate those.
        if (isset($data['training_level'])) {
            $ratings = Rating::find(explode('+', $data['training_level']));

            // Check if user is active in the area if required by setting
            $atcActivityRequired = Auth::user()->rating >= VatsimRating::S1->value && Setting::get('atcActivityBasedOnTotalHours') == false;
            $activeInArea = Auth::user()->atcActivity->firstWhere('area_id', $data['training_area']);
            if ($atcActivityRequired && $activeInArea && ! $activeInArea->atc_active) {
                return redirect()->back()->withErrors('You need to be active in the area to apply for training.');
            }

            // Check if user fulfill rating hour requirement
            $vatsimStats = [];
            $client = new \GuzzleHttp\Client();
            if (App::environment('production')) {
                $res = $client->request('GET', 'https://api.vatsim.net/v2/members/' . \Auth::id() . '/stats');
            } else {
                $res = $client->request('GET', 'https://api.vatsim.net/v2/members/819096/stats');
            }

            if ($res->getStatusCode() == 200) {
                $vatsimStats = json_decode($res->getBody(), true);

                if (isset($vatsimStats[strtolower(\Auth::user()->rating_short)])) {
                    $vatsimHours = $vatsimStats[strtolower(\Auth::user()->rating_short)];
                } else {
                    $vatsimHours = 0;
                }
            } else {
                return redirect()->back()->withErrors('We were unable to submit the application for you due to missing data from VATSIM. Please try again later.');
            }

            // Loop through the ratings applied for
            foreach ($ratings as $rating) {
                // Get the area specific requirements
                foreach ($rating->areas->where('id', $data['training_area']) as $area) {
                    if ($vatsimHours < $area->pivot->hour_requirement) {
                        return redirect()->back()->withErrors('You have insufficient hours on current rating to submit this application.');
                    }
                }
            }
        } elseif (isset($data['ratings'])) {
            $ratings = Rating::find($data['ratings']);

            // Missing fields? Return error
            if (! isset($data['training_area']) || ! isset($data['type'])) {
                return redirect()->back()->withErrors('One or more fields were missing');
            }

            // If it's a refresh training, force the training to refresh all endorsements in respective area or deny the creation
            if ($data['type'] == 2 || $data['type'] == 5) {
                // Ratings supplied in request
                $appliedRatings = $ratings->pluck('name');
                $validRefreshTraining = $this->validRefreshTraining($data['training_area'], $data['user_id'], $appliedRatings);

                if (! $validRefreshTraining['success']) {
                    return redirect()->back()->withErrors('A refresh/familiarisation training requires the student to refresh all of their active endorsements. Add these to the application and try again: ' . $validRefreshTraining['data']->implode(', '));
                }
            }

        } else {
            return redirect()->back()->withErrors('One or more ratings need to be selected to create training request.');
        }

        $training = Training::create([
            'user_id' => isset($data['user_id']) ? $data['user_id'] : \Auth::id(),
            'created_by' => \Auth::id(),
            'area_id' => $data['training_area'],
            'motivation' => isset($data['motivation']) ? $data['motivation'] : '',
            'experience' => isset($data['experience']) ? $data['experience'] : null,
            'english_only_training' => array_key_exists('englishOnly', $data) ? true : false,
            'type' => isset($data['type']) ? $data['type'] : 1,
        ]);

        if (isset($data['comment'])) {
            TrainingActivityController::create($training->id, 'COMMENT', null, null, null, 'Comment from application: ' . $data['comment']);
        }

        if ($ratings->count() > 1) {
            $training->ratings()->saveMany($ratings);
        } else {
            $training->ratings()->save($ratings->first());
        }

        ActivityLogController::info('TRAINING', 'Created training request ' . $training->id . ' for CID ' . $training->user_id . ' ― Ratings: ' . $ratings->pluck('name') . ' in ' . Area::find($training->area_id)->name);

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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
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
                return (is_a($a, '\App\Models\TrainingExamination')) ? -1 : 1;
            }

            return ($aSort > $bSort) ? -1 : 1;
        });

        $trainingMentors = $training->area->mentors->sortBy('name');
        $statuses = TrainingController::$statuses;
        $types = TrainingController::$types;
        $experiences = TrainingController::$experiences;
        $activities = $training->activities->sortByDesc('created_at');

        $trainingInterests = TrainingInterest::where('training_id', $training->id)->orderBy('created_at', 'DESC')->get();
        $activeTrainingInterest = TrainingInterest::where('training_id', $training->id)->where('expired', false)->get()->count();

        $relatedTasks = $training->tasks->sortByDesc('created_at');

        $requestTypes = TaskController::getTypes();
        $requestPopularAssignees = TaskController::getPopularAssignees($training->area);

        return view('training.show', compact('training', 'reportsAndExams', 'trainingMentors', 'statuses', 'types', 'experiences', 'activities', 'trainingInterests', 'activeTrainingInterest', 'relatedTasks', 'requestTypes', 'requestPopularAssignees'));
    }

    /**
     * Create a new instance of the resourcebundle_count
     *
     * @param  \Illuminate\Http\Request  $request
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
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateRequest(Training $training)
    {
        $this->authorize('update', $training);
        $attributes = $this->validateUpdateEdit();

        // Lets remeber what it was before for showing the change in logs
        $preChangeRatings = $training->ratings;
        $preChangeType = $training->type;

        // If it's a refresh training, validate the requested endorsements
        if ($attributes['type'] == 2 || $attributes['type'] == 5) {
            $appliedRatings = Rating::find($attributes['ratings'])->pluck('name');
            $validRefreshTraining = $this->validRefreshTraining($training->area_id, $training->user_id, $appliedRatings);

            if (! $validRefreshTraining['success']) {
                return redirect()->back()->withErrors('A refresh/familiarisation training requires the student to refresh all of their active endorsements. Add these to the application and try again: ' . $validRefreshTraining['data']->implode(', '));
            }
        }

        // Detach all ratings connceed to training to save the new (or same) ones.
        $training->ratings()->detach();

        // Check if ratings has been provided at all
        if (isset($attributes['ratings'])) {
            $ratings = Rating::find($attributes['ratings']);
        } else {
            return redirect()->back()->withErrors('One or more ratings need to be selected to update training request.');
        }

        // Save the ratings
        if ($ratings->count() > 1) {
            $training->ratings()->saveMany($ratings);
        } else {
            $training->ratings()->save($ratings->first());
        }

        // Save the rest
        $training->type = $attributes['type'];
        $training->english_only_training = array_key_exists('englishOnly', $attributes) ? true : false;

        $training->save();

        // Log the action
        ActivityLogController::warning('TRAINING', 'Updated training request ' . $training->id .
        ' ― Old Ratings: ' . $preChangeRatings->pluck('name') .
        ' ― New Ratings: ' . $ratings->pluck('name') .
        ' ― Old Training type: ' . TrainingController::$types[$preChangeType]['text'] .
        ' ― New Training type: ' . TrainingController::$types[$training->type]['text'] .
        ' ― English only: ' . ($training->english_only_training ? 'true' : 'false'));

        if ($preChangeType != $training->type) {
            TrainingActivityController::create($training->id, 'TYPE', $training->type, $preChangeType, Auth::user()->id);
        }

        return redirect($training->path())->withSuccess('Training successfully updated');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateDetails(Training $training)
    {
        $this->authorize('update', $training);
        $oldStatus = $training->fresh()->status;

        $attributes = $this->validateUpdateDetails();
        if (array_key_exists('status', $attributes)) {

            // Don't allow re-opening a training if that causes student to have multiple trainings at the same time
            if ($attributes['status'] >= 0 && $oldStatus < 0 && $training->user->hasActiveTrainings(true)) {
                if ($training->user->hasActiveTrainings(true)) {
                    return redirect($training->path())->withErrors('Training can not be reopened. The student already has an active training request.');
                }
            }

            $training->updateStatus($attributes['status']);

            if ($attributes['status'] != $oldStatus) {
                if ($attributes['status'] == -2 || $attributes['status'] == -4) {
                    TrainingActivityController::create($training->id, 'STATUS', $attributes['status'], $oldStatus, Auth::user()->id, $attributes['closed_reason']);
                } else {
                    TrainingActivityController::create($training->id, 'STATUS', $attributes['status'], $oldStatus, Auth::user()->id);
                }
            }
        }

        $notifyOfNewMentor = false;
        if (array_key_exists('mentors', $attributes)) {
            foreach ((array) $attributes['mentors'] as $mentor) {
                if (! $training->mentors->contains($mentor) && User::find($mentor) != null && User::find($mentor)->isMentorOrAbove($training->area)) {
                    $training->mentors()->attach($mentor, ['expire_at' => now()->addMonths(12)]);

                    // Notify student of their new mentor
                    $notifyOfNewMentor = true;

                    TrainingActivityController::create($training->id, 'MENTOR', $mentor, null, Auth::user()->id);
                }
            }

            foreach ($training->mentors as $mentor) {
                if (! in_array($mentor->id, (array) $attributes['mentors'])) {
                    $training->mentors()->detach($mentor);
                    TrainingActivityController::create($training->id, 'MENTOR', null, $mentor->id, Auth::user()->id);
                }
            }

            // Notify student of their new mentor. We put this here so detached mentors ain't included.
            if ($notifyOfNewMentor) {
                $training->user->notify(new TrainingMentorNotification($training));
            }

            unset($attributes['mentors']);
        } else {
            // Detach all if no passed key, as that means the list is empty

            foreach ($training->mentors as $mentor) {
                TrainingActivityController::create($training->id, 'MENTOR', null, $mentor->id, Auth::user()->id);
            }

            $training->mentors()->detach();
        }

        // Update paused time for queue estimation
        if (isset($attributes['paused_at']) && (int) $training->status >= TrainingStatus::IN_QUEUE->value) {
            if (! isset($training->paused_at)) {
                $attributes['paused_at'] = Carbon::now();
                TrainingActivityController::create($training->id, 'PAUSE', 1, null, Auth::user()->id);
            } else {
                $attributes['paused_at'] = $training->paused_at;
            }
        } else {
            // If paused is unchecked but training is paused, sum up the length and unpause.
            if (isset($training->paused_at)) {
                $training->paused_length = $training->paused_length + Carbon::create($training->paused_at)->diffInSeconds(Carbon::now(), true);
                $training->update(['paused_length' => $training->paused_length]);
                TrainingActivityController::create($training->id, 'PAUSE', 0, null, Auth::user()->id);
            }

            $attributes['paused_at'] = null;
        }

        // If training is closed, force to unpause
        if ((int) $training->status != $oldStatus) {
            if ((int) $training->status < TrainingStatus::IN_QUEUE->value) {
                $attributes['paused_at'] = null;
                if (isset($training->paused_at)) {
                    TrainingActivityController::create($training->id, 'PAUSE', 0, null, Auth::user()->id);
                }
            }
        }

        // Update the training
        $training->update($attributes);

        ActivityLogController::warning('TRAINING', 'Updated training details ' . $training->id .
        ' ― Old Status: ' . TrainingController::$statuses[$oldStatus]['text'] .
        ' ― New Status: ' . TrainingController::$statuses[$training->status]['text'] .
        ' ― Mentor: ' . $training->mentors->pluck('name'));

        // Send e-mail and store endorsements rating (non-GRP ones), if it's a new status and it goes from active to closed
        if ((int) $training->status != $oldStatus) {
            if ((int) $training->status < TrainingStatus::IN_QUEUE->value) {
                // Detach all mentors
                $training->mentors()->detach();

                // If the training was completed and double checked with a passed exam result, store the relevant endorsements
                if ((int) $training->status == TrainingStatus::COMPLETED->value) {
                    foreach ($training->ratings as $rating) {
                        if ($rating->vatsim_rating == null) {
                            // Revoke the old endorsement if active
                            $oldEndorsement = $training->user->endorsements->where('type', 'FACILITY')->where('revoked', false)->where('expired', false);
                            foreach ($oldEndorsement as $oe) {
                                foreach ($oe->ratings as $oer) {
                                    if ($oer->id == $rating->id) {
                                        $oe->revoked = true;
                                        $oe->valid_to = now();
                                        $oe->save();
                                        break;
                                    }
                                }
                            }

                            // All clear, let's start by attemping the insertion to the API
                            $response = DivisionApi::assignTierEndorsement($training->user, $rating, Auth::id());
                            if ($response && $response->failed()) {
                                return back()->withErrors('Request failed due to error in ' . DivisionApi::getName() . ' API: ' . $response->json()['message']);
                            }

                            // Grant new endorsement
                            $endorsement = new \App\Models\Endorsement();
                            $endorsement->user_id = $training->user->id;
                            $endorsement->type = 'FACILITY';
                            $endorsement->valid_from = now()->format('Y-m-d H:i:s');
                            $endorsement->valid_to = null;
                            $endorsement->issued_by = null;
                            $endorsement->save();

                            $endorsement->ratings()->save(Rating::find($rating->id));
                        }
                    }
                }

                // If training is completed with a passed exam result, let's set the user to active
                if ((int) $training->status == TrainingStatus::COMPLETED->value) {
                    // If training is [Refresh, Transfer or Fast-track] or [Standard and exam is passed]
                    if (! Setting::get('atcActivityBasedOnTotalHours') || Setting::get('atcActivityBasedOnTotalHours') && $training->type <= 4) {
                        try {
                            $activity = AtcActivity::where('user_id', $training->user->id)->where('area_id', $training->area->id)->firstOrFail();
                            $activity->atc_active = true;
                            $activity->start_of_grace_period = now();
                            $activity->save();
                        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                            AtcActivity::create([
                                'user_id' => $training->user->id,
                                'area_id' => $training->area->id,
                                'hours' => 0,
                                'atc_active' => true,
                                'start_of_grace_period' => now(),
                            ]);
                        }
                    }
                }

                $training->user->notify(new TrainingClosedNotification($training, (int) $training->status, $training->closed_reason));

                return redirect($training->path())->withSuccess('Training successfully closed. E-mail confirmation sent to the student.');
            }

            if ((int) $training->status == TrainingStatus::PRE_TRAINING->value) {
                $training->user->notify(new TrainingPreStatusNotification($training));

                return redirect($training->path())->withSuccess('Training successfully updated. E-mail confirmation of pre-training sent to the student.');
            }
        }

        if ($notifyOfNewMentor) {
            return redirect($training->path())->withSuccess('Training successfully updated. E-mail notification of mentor assigned sent to the student.');
        }

        return redirect($training->path())->withSuccess('Training successfully updated');
    }

    /**
     * Close the specified resource in storage.
     *
     * @param string
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function close(Training $training)
    {
        $this->authorize('close', $training);
        ActivityLogController::warning('TRAINING', 'Student closed training request ' . $training->id .
        ' ― Status: ' . TrainingController::$statuses[$training->status]['text'] .
        ' ― Training type: ' . TrainingController::$types[$training->type]['text']);
        TrainingActivityController::create($training->id, 'STATUS', -3, $training->status, $training->user->id);

        $training->mentors()->detach();
        $training->updateStatus(-3);

        $training->user->notify(new TrainingClosedNotification($training, (int) $training->status));

        return redirect($training->path())->withSuccess('Training successfully closed.');
    }

    /**
     * Mark specific resource as pre-training is completed in storage
     *
     * @param Training
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function togglePreTrainingCompleted(Training $training)
    {
        $this->authorize('togglePreTrainingCompleted', $training);

        // Fetch the user, states and update them
        $user = Auth::user();
        $state = $training->pre_training_completed;
        $newState = ! $state;
        $newStateText = (($newState) ? 'completed' : 'not completed');

        // Update the state in database
        $training->pre_training_completed = $newState;
        $training->save();

        // Logging
        ActivityLogController::warning('TRAINING', 'Student marked pre-training as completed ' . $training->id);
        TrainingActivityController::create($training->id, 'PRETRAINING', $newState, $state, $user->id);

        return redirect($training->path())->withSuccess('Pre-training marked as ' . $newStateText);
    }

    /**
     * Confirm the continued interest in the training
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function confirmInterest(Training $training, string $key)
    {
        // Only allow the training student to confirm the interest
        if (Auth::id() != $training->user->id) {
            return abort(403);
        }

        $interest = TrainingInterest::where('training_id', $training->id)->where('key', $key)->orderBy('created_at')->get()->last();

        if (isset($interest)) {
            // Check if already confirmed
            if ($interest->confirmed_at) {
                return redirect($training->path())->withSuccess('You have already confirmed your interest for this training.');
            }

            if ($interest->expired) {
                return redirect($training->path())->withErrors('This training interest link has expired. Please contact staff.');
            }

            $interest->confirmed_at = now();
            $interest->updated_at = now();
            $interest->expired = true;
            $interest->save();

            ActivityLogController::info('TRAINING', 'Training interest confirmed.');

            return redirect()->to($training->path())->withSuccess('Interest successfully confirmed');
        }

        return redirect()->to($training->path())->withErrors('We could not find a training interest confirmation for this training. Please contact our technical staff if this issue persists.');
    }

    /**
     * Return if the refresh is correct. If not, returns descrepency rating names
     */
    protected function validRefreshTraining($areaId, $userId, $requestedRatings)
    {
        // Ratings applicable to area of request
        $areaRatings = Rating::whereHas('areas', function ($query) use ($areaId) {
            $query->where('area_id', $areaId);
        })->whereNull('vatsim_rating')->get()->pluck('name');

        // Ratings which user has today and needs to be refresh
        $userRatings = User::find($userId)->endorsements->where('type', 'FACILITY')->where('expired', false)->where('revoked', false)->map(function ($endorsement) {
            return $endorsement->ratings->first()->name;
        });

        // Gather expected ratings for this user in given area
        $expectedRatings = collect();
        foreach ($userRatings as $userRating) {
            if ($areaRatings->contains($userRating)) {
                $expectedRatings->push($userRating);
            }
        }

        $discrepancyRatings = $expectedRatings->diff($requestedRatings);
        if ($discrepancyRatings->count() > 0) {
            return ['success' => false, 'data' => $discrepancyRatings];
        }

        return ['success' => true];
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
            'motivation' => 'sometimes|max:1500',
            'user_id' => 'sometimes|required|integer',
            'comment' => 'nullable',
            'training_level' => 'sometimes|required',
            'ratings' => 'sometimes|required',
            'training_area' => 'sometimes|required',
            'status' => 'sometimes|required|integer',
            'type' => 'sometimes|integer',
            'mentors' => 'sometimes',
            'closed_reason' => 'sometimes|max:65',
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
