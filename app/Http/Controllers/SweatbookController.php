<?php

namespace App\Http\Controllers;

use App\Sweatbook;
use App\Position;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SweatbookController extends Controller
{  
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){
        $user = Auth::user();
        $bookings = Sweatbook::all()->sortBy('date');
        
        if($user->isMentor()) return view('sweatbox.calendar', compact('bookings', 'user'));
        
        abort(403);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(){
        $user = Auth::user();
        $positions = Position::all();

        if($user->isMentor()) return view('sweatbox.create', compact('positions'));

        abort(403);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Booking $booking
     * @return \Illuminate\Http\Response
     */
    public function show($id){
        $booking = Sweatbook::findOrFail($id);
        $positions = Position::all();
        $user = Auth::user();

        if ($booking->mentor == $user->id || $user->isModerator()) return view('sweatbox.show', compact('booking', 'positions'));

        abort(403);
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
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'mentor_notes' => 'nullable|string|max:255'
        ]);

        $user = Auth::user();

        if($user->isMentor()) {
            $date = Carbon::createFromFormat('d/m/Y', $data['date']);
            $booking = new Sweatbook();
            
            $booking->user_id = $user->id;
            $booking->date = $date->format('Y-m-d'); 
            $booking->start_at = Carbon::createFromFormat('H:i', $data['start_at']);
            $booking->end_at = Carbon::createFromFormat('H:i', $data['end_at']);
            $booking->position_id = Position::all()->firstWhere('callsign', strtoupper($data['position']))->id;
            $booking->mentor_notes = $data['mentor_notes'];

            if($booking->start_at->diffInMinutes($booking->end_at, false) <= 0) return back()->withInput()->withErrors('Booking need to have a valid duration!');
            if($booking->start_at->diffInMinutes(Carbon::now(), false) > 0) return back()->withErrors('You cannot create a booking in the past.')->withInput();

            $booking->save();
        }

        return redirect('/sweatbox')->withSuccess("Booking successfully added.");
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
 
    public function update(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'mentor_notes' => 'nullable|string|max:255'
        ]);

        $user = Auth::user();
        $booking = Sweatbook::findOrFail($request->id);

        if($user->id == $booking->mentor || $user->isModerator()) {
            $date = Carbon::createFromFormat('d/m/Y', $data['date']);

            $booking->user_id = $booking->user_id;
            $booking->date = $date->format('Y-m-d'); 
            $booking->start_at = Carbon::createFromFormat('H:i', $data['start_at']);
            $booking->end_at = Carbon::createFromFormat('H:i', $data['end_at']);
            $booking->position_id = Position::all()->firstWhere('callsign', strtoupper($data['position']))->id;
            $booking->mentor_notes = $data['mentor_notes'];

            if($booking->start_at->diffInMinutes($booking->end_at, false) <= 0) return back()->withInput()->withErrors('Booking need to have a valid duration!');
            if($booking->start_at->diffInMinutes(Carbon::now(), false) > 0) return back()->withErrors('You cannot create a booking in the past.')->withInput();

            $booking->save();
        }

        return redirect('/sweatbox')->withSuccess("Booking successfully added.");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Booking  $booking
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $user = Auth::user();
        $booking = Sweatbook::findOrFail($id);

        if($user->id == $booking->mentor || $user->isModerator()) $booking->delete();

        return redirect('/sweatbox');
    }
}