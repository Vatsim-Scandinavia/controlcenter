<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Position;
use App\Models\SoloEndorsement;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * This controller handles the solo endorsement assignent.
 */
class SoloEndorsementController extends Controller
{

    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('view', SoloEndorsement::class);

        $user = Auth::user();
        $endorsements = SoloEndorsement::all();
        return view('user.soloendorsement.index', compact('user', 'endorsements'));
    }

    /**
     * Display a listing of the resource available for everyone, but ment for SUPs
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function sup()
    {
        if(\Auth::user() && \Auth::user()->isMentorOrAbove()) return redirect(route('users.soloendorsements'));

        $endorsements = SoloEndorsement::all();
        return view('user.soloendorsement.sup', compact('endorsements'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create()
    {
        $this->authorize('create', SoloEndorsement::class);
        $user = Auth::user();
        $students = User::with('trainings')->has('trainings')->get();
        $positions = Position::all();

        return view('user.soloendorsement.create', compact('students', 'positions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store()
    {
        $this->authorize('update', SoloEndorsement::class);

        $data = request()->validate([
            'student' => 'required|numeric',
            'expires' => 'required|date_format:d/m/Y|after_or_equal:today|before_or_equal:'.\Carbon\Carbon::createFromTime()->addMonth(),
            'position' => 'required|exists:positions,callsign'
        ]);

        // Check if student exists
        $user = User::find($data['student']);
        if(!$user) return back()->withInput()->withErrors(['student' => 'Invalid user']);
    
        // Check if endoresement for this student already exists
        $existingEndorsement = SoloEndorsement::where('user_id', $user->id)->count();
        if($existingEndorsement) return back()->withInput()->withErrors(['student' => 'This student already has an active solo endorsement']);

        $expireDate = Carbon::createFromFormat('d/m/Y', $data['expires']);
        $expireDate->setTime(12, 0);

        $endorsement = new SoloEndorsement();

        $endorsement->user_id = $user->id;
        $endorsement->training_id = $user->trainings->first()->id;
        $endorsement->position = $data['position'];
        $endorsement->expires_at = $expireDate->format('Y-m-d H:i:s');

        $endorsement->save();

        ActivityLogController::info('TRAINING', 'Created solo endorsement '.
        ' ― Student: '.$endorsement->user_id.
        ' ― Position: '.$endorsement->position.
        ' ― Expires: '.Carbon::parse($endorsement->expires_at)->toEuropeanDate());

        return redirect()->intended(route('users.soloendorsements'))->withSuccess($user->name . "'s endorsement created");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete($id)
    {
        $this->authorize('update', SoloEndorsement::class);
        $user = Auth::user();
        $endorsement = SoloEndorsement::findOrFail($id);

        $endorsement->delete();

        ActivityLogController::warning('TRAINING', 'Deleted solo endorsement '.
        ' ― Student: '.$endorsement->user_id.
        ' ― Position: '.$endorsement->position.
        ' ― Expires: '.Carbon::parse($endorsement->expires_at)->toEuropeanDate());

        return redirect()->intended(route('users.soloendorsements'))->withSuccess($user->name . "'s endorsement deleted");
    }
}
