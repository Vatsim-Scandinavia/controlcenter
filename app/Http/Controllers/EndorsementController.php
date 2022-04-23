<?php

namespace App\Http\Controllers;

use App\Models\Endorsement;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Rating;
use App\Models\Area;
use App\Models\Position;
use Carbon\Carbon;
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
        $this->authorize('create', Endorsement::class);
        $users = User::all();
        $positions = Position::all();
        $areas = Area::all();
        $ratingsMASC = Rating::where('vatsim_rating', null)->get();
        $ratingsGRP = Rating::where('vatsim_rating', '<=', 7)->get();

        return view('endorsements.create', compact('users', 'positions', 'areas', 'ratingsMASC', 'ratingsGRP'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // Get the type before we fully validate
        $typeValidation = $request->only(['endorsementType']);
        $endorsementType = $typeValidation["endorsementType"];
        $this->authorize('create', [Endorsement::class, $endorsementType]);

        $data = [];
        
        if($endorsementType == "MASC"){
            // Major Airport / Special Center endorsement

            $data = request()->validate([
                'user' => 'required|numeric|exists:App\Models\User,id',
                'ratingMASC' => 'required|exists:App\Models\Rating,id'
            ]);
            $user = User::find($data['user']);

            // Check if endoresement for this user already exists
            $existingEndorsements = Endorsement::where('user_id', $user->id)->where('type', 'MASC')->get();
            foreach($existingEndorsements as $e){
                foreach($e->ratings as $r){
                    if($r->id == $data['ratingMASC']){
                        return back()->withInput()->withErrors(['ratingMASC' => $user->name.' already has an endorsement for '.$r->name]);
                    }
                }
            }

            // All clear, create endorsement
            $endorsement = $this->createEndorsementModel($endorsementType, $user);

            // Add ratings
            $endorsement->ratings()->save(Rating::find($data['ratingMASC']));

            ActivityLogController::info('OTHER', 'Created Airport/Center endorsement '.
            ' ― User: '.$endorsement->user_id.
            ' ― Rating: '.Rating::find($data['ratingMASC'])->name);

            return redirect()->intended(route('endorsements.mascs'))->withSuccess($user->name . "'s endorsement created");





        } elseif($endorsementType == "TRAINING"){
            // Training endorsements Solo or S1
            
            $data = request()->validate([
                'user' => 'required|numeric|exists:App\Models\User,id',
                'trainingType' => ['required', 'regex:/(SOLO|S1)/i'],
                'expires' => 'required|date_format:d/m/Y|after_or_equal:today|before_or_equal:'.\Carbon\Carbon::createFromTime()->addMonth(),
                'positions' => "required",
            ]);
            $user = User::find($data['user']);
            $trainingType = $data['trainingType'];
            $expireDate = Carbon::createFromFormat('d/m/Y', $data['expires']);
            $expireDate->setTime(23, 59);

            // Validate that this user has other endrosement of this type from before
            if($this->checkEndorsementExists($user, $trainingType)) return back()->withInput()->withErrors($user->name.' has already an active '.$trainingType.' training endorsement. Revoke it first, to create a new one.');

            // Validate that solo only has one position and set expire time
            if($trainingType == "SOLO"){

                $expireDate->setTime(12, 0);

                if(str_contains($data['positions'], ',')){
                    return back()->withInput()->withErrors(['positions' => 'Solo endorsement can only have one assigned position']);
                }
            }
            
            // All clear, create endorsement
            $endorsement = $this->createEndorsementModel($trainingType, $user, $expireDate->format('Y-m-d H:i:s'));

            // Add positions
            if($trainingType == "SOLO"){
                $endorsement->positions()->save(Position::where('callsign', $data['positions'])->get()->first());
            } else {
                // Are more than one positions defined?
                if(str_contains($data['positions'], ',')){
                    $endorsement->positions()->saveMany(Position::whereIn('callsign', explode(",", $data["positions"]))->get());
                } else {
                    $endorsement->positions()->save(Position::where('callsign', $data['positions'])->get()->first());
                }
            }

            ActivityLogController::info('TRAINING', 'Created '.$trainingType.' endorsement '.
            ' ― User: '.$endorsement->user_id.
            ' ― Positions: '.$data['positions']);

            return redirect()->intended(route('endorsements.trainings'))->withSuccess($user->name . "'s ".$trainingType." endorsement successfully created");
            
            



        } elseif($endorsementType == "EXAMINER") {
            // Examiner endorsement

            $data = request()->validate([
                'user' => 'required|numeric|exists:App\Models\User,id',
                'ratingsExaminate' => 'required',
                'areas' => 'required',
            ]);
            $user = User::find($data['user']);

            // Check if already holding examiner endorsement
            if($this->checkEndorsementExists($user, $endorsementType)) return back()->withInput()->withErrors($user->name.' has already an '.$endorsementType.' endorsement. Revoke it first, to create a new one.');

            // All clear, create endorsement
            $endorsement = $this->createEndorsementModel($endorsementType, $user);

            // Attach ratings and areas
            $endorsement->ratings()->saveMany(Rating::find($data['ratingsExaminate']));
            $endorsement->areas()->saveMany(Area::find($data['areas']));

            ActivityLogController::info('OTHER', 'Created '.$endorsementType.' endorsement '.
            ' ― User: '.$endorsement->user_id.
            ' ― Ratings: '.implode(',', $data['ratingsExaminate']).
            ' ― Areas: '.implode(',', $data['areas']));

            return redirect()->intended(route('endorsements.examiners'))->withSuccess($user->name . "'s examiner endorsement successfully created");
        





        } elseif($endorsementType == "VISITING") {
            // Visiting endorsement

            $data = request()->validate([
                'user' => 'required|numeric|exists:App\Models\User,id',
                'ratingGRP' => 'required|integer|exists:App\Models\Rating,id',
                'areas' => 'required',
                'visitingEndorsements' => 'required',
            ]);
            $user = User::find($data['user']);

            // Check if already holding visiting endorsement
            if($this->checkEndorsementExists($user, $endorsementType)) return back()->withInput()->withErrors($user->name.' has already an '.$endorsementType.' endorsement. Revoke it first, to create a new one.');

            // All clear, create endorsement
            $endorsement = $this->createEndorsementModel($endorsementType, $user);

            // Attach ratings and areas
            $endorsement->areas()->saveMany(Area::find($data['areas']));
            $endorsement->ratings()->save(Rating::find($data['ratingGRP']));
            $endorsement->ratings()->saveMany(Rating::find($data['visitingEndorsements']));
            
            ActivityLogController::info('OTHER', 'Created '.$endorsementType.' endorsement '.
            ' ― User: '.$endorsement->user_id.
            ' ― Areas: '.implode(',', $data['areas']).
            ' ― Endorsements: '.implode(',', $data['visitingEndorsements']));

            return redirect()->intended(route('endorsements.visitors'))->withSuccess($user->name . "'s visiting endorsement successfully created");

        }

        // We shouldn't get this far, throw error
        abort(501);
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

    /**
     * Private function to create an endorsement object
     *
     * @param  String  $endorsementType
     * @param  \App\Models\User  $user
     * @param  String  $valid_to
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

    /**
     * Private function to check if endorsement type exists
     *
     * @param  \App\Models\User  $user
     * @param  String  $endorsementType
     * @return boolean
     */
    private function checkEndorsementExists(User $user, $endorsementType)
    {
        return Endorsement::where('user_id', $user->id)->where('type', $endorsementType)->get()->count();
    }
}
