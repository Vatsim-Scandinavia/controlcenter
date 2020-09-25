<?php

namespace App\Http\Controllers;

use App\User;
use App\Position;
use App\SoloEndorsement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SoloEndorsementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $endorsements = SoloEndorsement::all();
        if($user->isMentor()) return view('user.soloendorsement.index', compact('user', 'endorsements'));

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

        if($user->isModerator()) return view('user.soloendorsement.create', compact('students', 'positions'));

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

            return redirect()->intended(route('users.soloendorsements'))->withSuccess($user->name . "'s endorsement created");
        }

        abort(403);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $user = Auth::user();
        $endorsement = SoloEndorsement::findOrFail($id);

        if($user->isModerator()) $endorsement->delete();

        return redirect()->intended(route('users.soloendorsements'))->withSuccess($user->name . "'s endorsement deleted");
    }
}
