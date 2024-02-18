<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Position;
use App\Models\Rating;
use App\Models\User;
use App\Notifications\EndorsementCreatedNotification;
use App\Notifications\EndorsementModifiedNotification;
use App\Notifications\EndorsementRevokedNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        $users = User::whereHas('endorsements', function (Builder $query) {
            $query->where('type', 'MASC')->where('revoked', false)->where('expired', false);
        })->with('endorsements', 'endorsements.ratings')->get();
        $ratings = Rating::whereNull('vatsim_rating')->get()->sortBy('name');

        return view('endorsements.mascs', compact('users', 'ratings'));
    }

    /**
     * Display a listing of the Solo endorsement
     *
     * @return \Illuminate\Http\Response
     */
    public function indexSolos()
    {
        $endorsements = Endorsement::where('type', 'SOLO')->with('positions', 'user')
            ->where(function ($q) {
                $q->orWhere(function ($q2) {
                    $q2->where('expired', false)
                        ->where('revoked', false);
                })
                    ->orWhere(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->where('valid_to', '>=', Carbon::now()->subDays(14));
                        })
                            ->where(function ($q3) {
                                $q3->where('expired', true)
                                    ->orWhere('revoked', true);
                            });
                    });
            })
            ->get();

        // Sort endorsements
        $endorsements = $endorsements->sortByDesc('valid_to');

        return view('endorsements.solos', compact('endorsements'));
    }

    /**
     * Display a listing of the users with examiner endorsements
     *
     * @return \Illuminate\Http\Response
     */
    public function indexExaminers()
    {
        $endorsements = Endorsement::where('type', 'EXAMINER')->where('revoked', false)->get();
        $areas = Area::all();

        return view('endorsements.examiners', compact('endorsements', 'areas'));
    }

    /**
     * Display a listing of the users with visiting endorsements
     *
     * @return \Illuminate\Http\Response
     */
    public function indexVisitors()
    {
        $endorsements = Endorsement::where('type', 'VISITING')->where('revoked', false)->with('user', 'ratings', 'areas.ratings')->get();
        $areas = Area::all();

        return view('endorsements.visiting', compact('endorsements', 'areas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($prefillUserId = null)
    {
        $this->authorize('create', Endorsement::class);
        if ($prefillUserId) {
            $users = collect(User::where('id', $prefillUserId)->get());
        } else {
            $users = User::all();
        }
        $positions = Position::all();
        $areas = Area::all();
        $ratingsMASC = Rating::where('vatsim_rating', null)->get();
        $ratingsGRP = Rating::where('vatsim_rating', '<=', 7)->get();

        return view('endorsements.create', compact('users', 'positions', 'areas', 'ratingsMASC', 'ratingsGRP', 'prefillUserId'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Get the type before we fully validate
        $typeValidation = $request->only(['endorsementType']);
        $endorsementType = $typeValidation['endorsementType'];
        $this->authorize('create', [Endorsement::class, $endorsementType]);

        $data = [];

        if ($endorsementType == 'MASC') {
            // Major Airport / Special Center endorsement

            $data = request()->validate([
                'user' => 'required|numeric|exists:App\Models\User,id',
                'ratingMASC' => 'required|exists:App\Models\Rating,id',
            ]);
            $user = User::find($data['user']);

            // Check if endoresement for this user already exists
            $existingEndorsements = Endorsement::where('user_id', $user->id)->where('type', 'MASC')->where('revoked', false)->where('expired', false)->get();
            foreach ($existingEndorsements as $e) {
                foreach ($e->ratings as $r) {
                    if ($r->id == $data['ratingMASC']) {
                        return back()->withInput()->withErrors(['ratingMASC' => $user->name . ' already has an endorsement for ' . $r->name]);
                    }
                }
            }

            // All clear, create endorsement
            $endorsement = $this->createEndorsementModel($endorsementType, $user);

            // Add ratings
            $endorsement->ratings()->save(Rating::find($data['ratingMASC']));

            ActivityLogController::warning('ENDORSEMENT', 'Created Airport/Center endorsement ' .
            ' ― User: ' . $endorsement->user_id .
            ' ― Rating: ' . Rating::find($data['ratingMASC'])->name);

            return redirect()->intended(route('user.show', $user->id))->withSuccess($user->name . "'s endorsement created");
        } elseif ($endorsementType == 'SOLO') {
            // Training endorsements Solo

            $data = request()->validate([
                'user' => 'required|numeric|exists:App\Models\User,id',
                'expires' => 'sometimes|date_format:d/m/Y',
                'expireInf' => 'sometimes',
                'position' => 'required',
            ]);
            $user = User::find($data['user']);
            $expireInfinite = isset($data['expireInf']) ? true : false;
            $linkedToTraining = true;

            // Let's validate the expire date
            if (! $expireInfinite) {
                $expireDate = Carbon::createFromFormat('d/m/Y', $data['expires']);
                $expireDate->setTime(23, 59);

                $dateExpires = Carbon::createFromFormat('d/m/Y', $data['expires'])->startOfDay();
                if (($dateExpires->lessThan(Carbon::today()) || $dateExpires->greaterThan(Carbon::today()->addMonth()))) {
                    return back()->withInput()->withErrors(['expires' => 'Solo endorsements must expire within 30 days from today']);
                }
            } else {
                $expireDate = null;
                $linkedToTraining = false;
            }

            // Validate that this user has other endrosement of this type from before
            if ($user->hasActiveEndorsement('SOLO')) {
                return back()->withInput()->withErrors($user->name . ' has already an active solo endorsement. Revoke it first, to create a new one.');
            }

            // If it's not a infinite endorsement, it has to be tied to an existing training
            if (! $expireInfinite && $user->trainings->where('status', '>=', 0)->count() == 0) {
                return back()->withInput()->withErrors($user->name . ' has no active training to link this endorsement to.');
            }

            // Validate that solo only has one position and set expire time
            if ($expireDate != null) {
                $expireDate->setTime(12, 0);
            }

            // All clear, create endorsement
            if ($expireDate != null) {
                $endorsement = $this->createEndorsementModel('SOLO', $user, $expireDate->format('Y-m-d H:i:s'));
            } else {
                $endorsement = $this->createEndorsementModel('SOLO', $user, $expireDate);
            }

            // Add positions
            $endorsement->positions()->save(Position::where('callsign', $data['position'])->get()->first());

            ActivityLogController::warning('ENDORSEMENT', 'Created SOLO endorsement ' .
            ' ― User: ' . $endorsement->user_id .
            ' ― Positions: ' . $data['position']);

            // Log this new endorsement to the user's active training
            if ($linkedToTraining) {
                TrainingActivityController::create($user->trainings->where('status', '>=', 0)->first()->id, 'ENDORSEMENT', $endorsement->id, null, Auth::user()->id, $endorsement->positions->pluck('callsign')->implode(', '));
            }

            $user->notify(new EndorsementCreatedNotification($endorsement));

            return redirect()->intended(route('user.show', $user->id))->withSuccess($user->name . '\'s solo endorsement successfully created. E-mail confirmation sent to the student.');
        } elseif ($endorsementType == 'EXAMINER') {
            // Examiner endorsement

            $data = request()->validate([
                'user' => 'required|numeric|exists:App\Models\User,id',
                'ratingGRP' => 'required|integer|exists:App\Models\Rating,id',
                'areas' => 'required',
            ]);
            $user = User::find($data['user']);

            // Check if already holding examiner endorsement
            if ($user->hasActiveEndorsement($endorsementType)) {
                return back()->withInput()->withErrors($user->name . ' has already an ' . $endorsementType . ' endorsement. Revoke it first, to create a new one.');
            }

            // All clear, create endorsement
            $endorsement = $this->createEndorsementModel($endorsementType, $user);

            // Attach ratings and areas
            $endorsement->ratings()->save(Rating::find($data['ratingGRP']));
            $endorsement->areas()->saveMany(Area::find($data['areas']));

            ActivityLogController::warning('ENDORSEMENT', 'Created ' . $endorsementType . ' endorsement ' .
            ' ― User: ' . $endorsement->user_id .
            ' ― Rating: ' . $data['ratingGRP'] .
            ' ― Areas: ' . implode(',', $data['areas']));

            return redirect()->intended(route('user.show', $user->id))->withSuccess($user->name . "'s examiner endorsement successfully created");
        } elseif ($endorsementType == 'VISITING') {
            // Visiting endorsement

            $data = request()->validate([
                'user' => 'required|numeric|exists:App\Models\User,id',
                'ratingGRP' => 'required|integer|exists:App\Models\Rating,id',
                'areas' => 'required',
            ]);
            $user = User::find($data['user']);

            // Check if already holding visiting endorsement
            if ($user->hasActiveEndorsement($endorsementType)) {
                return back()->withInput()->withErrors($user->name . ' has already an ' . $endorsementType . ' endorsement. Revoke it first, to create a new one.');
            }

            // All clear, create endorsement
            $endorsement = $this->createEndorsementModel($endorsementType, $user);

            // Attach ratings and areas
            $endorsement->areas()->saveMany(Area::find($data['areas']));
            $endorsement->ratings()->save(Rating::find($data['ratingGRP']));

            ActivityLogController::warning('ENDORSEMENT', 'Created ' . $endorsementType . ' endorsement ' .
            ' ― User: ' . $endorsement->user_id .
            ' ― Areas: ' . implode(',', $data['areas']));

            return redirect()->intended(route('user.show', $user->id))->withSuccess($user->name . "'s visiting endorsement successfully created");
        }

        // We shouldn't get this far, throw error
        abort(501);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Endorsement  $endorsement
     * @return \Illuminate\Http\Response
     */
    public function destroy($endorsementId)
    {
        $endorsement = Endorsement::findOrFail($endorsementId);
        $this->authorize('delete', [Endorsement::class, $endorsement]);
        $user = User::find($endorsement->user_id);

        if ($endorsement->revoked) {
            return redirect()->back()->withErrors($user->name . "'s " . $endorsement->type . ' endorsement is already revoked.');
        }

        $endorsement->revoked = true;
        $endorsement->revoked_by = \Auth::user()->id;
        $endorsement->valid_to = now();
        $endorsement->save();

        ActivityLogController::warning('ENDORSEMENT', 'Deleted ' . $user->name . '\'s ' . $endorsement->type . ' endorsement');
        if ($endorsement->type == 'SOLO') {
            $endorsement->user->notify(new EndorsementRevokedNotification($endorsement));

            return redirect()->back()->withSuccess(User::find($endorsement->user_id)->name . "'s " . $endorsement->type . ' endorsement revoked. E-mail confirmation sent to the student.');
        }

        return redirect()->back()->withSuccess(User::find($endorsement->user_id)->name . "'s " . $endorsement->type . ' endorsement revoked.');
    }

    /**
     * @param User user
     */
    public static function disableAtc($user)
    {
        $user->atc_active = false;
        $user->save();
    }

    /**
     * Shorten the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function shorten($endorsementId, $date)
    {
        $endorsement = Endorsement::findOrFail($endorsementId);
        $this->authorize('shorten', [Endorsement::class, $endorsement]);

        $date = Carbon::parse($date);

        if ($date->gt($endorsement->valid_to)) {
            return redirect()->back()->withErrors('You can not shorten an endorsement to a future date.');
        }

        $date->setHour(12)->setMinute(00);

        $endorsement->valid_to = $date;
        $endorsement->save();

        ActivityLogController::warning('ENDORSEMENT', 'Shortened ' . User::find($endorsement->user_id)->name . '\'s ' . $endorsement->type . ' endorsement to date ' . $date);
        $endorsement->user->notify(new EndorsementModifiedNotification($endorsement));

        return redirect()->back()->withSuccess(User::find($endorsement->user_id)->name . "'s " . $endorsement->type . ' endorsement shortened to ' . Carbon::parse($date)->toEuropeanDateTime() . '. E-mail sent to student.');
    }

    /**
     * Private function to create an endorsement object
     *
     * @param  string  $endorsementType
     * @param  string  $valid_to
     * @return \App\Models\Endorsement
     */
    private function createEndorsementModel($endorsementType, User $user, $valid_to = null)
    {
        $endorsement = new Endorsement();
        $endorsement->user_id = $user->id;
        $endorsement->type = $endorsementType;
        $endorsement->valid_from = now()->format('Y-m-d H:i:s');
        $endorsement->valid_to = $valid_to;
        $endorsement->issued_by = \Auth::user()->id;
        $endorsement->save();

        return $endorsement;
    }
}
