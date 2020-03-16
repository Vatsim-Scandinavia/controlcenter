<?php

namespace App\Http\Controllers;

use App\User;
use App\Position;
use App\UserEndorsement;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserEndorsementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $endorsements = UserEndorsement::all();
        if($user->isMentor()) return view('user.endorsement.overview', compact('user', 'endorsements'));

        abort(403);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = Auth::user();
        $students = User::with('trainings')->has('trainings')->get();
        $positions = Position::all();

        if($user->isModerator()) return view('user.endorsement.create', compact('students', 'positions'));

        abort(403);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        if(Auth::user()->isModerator()) {

            $data = request()->validate([
                'student' => 'required|numeric',
                'expires' => 'required|date:Y-m-d|after_or_equal:today|before_or_equal:'.\Carbon\Carbon::createFromTime()->addMonth(),
                'position' => 'required|exists:positions,callsign'
            ]);

            $user = User::find($data['student']);
            if(!$user) return back()->withErrors(['student' => 'Invalid user']);
        
            $expireDate = new DateTime($data['expires']);
            $endorsement = new UserEndorsement();

            $endorsement->user_id = $user->id;
            $endorsement->training_id = $user->trainings->first()->id;
            $endorsement->position = $data['position'];
            $endorsement->expires_at = $expireDate->format('Y-m-d');

            $endorsement->save();

            return redirect()->route('users.endorsements');
        }

        abort(403);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
}
