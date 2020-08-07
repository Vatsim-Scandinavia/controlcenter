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
        $positions = Position::all();
        
        if($user->isMentor()) return view('sweatbook.index', compact('bookings', 'user', 'positions'));
        
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

        if ($booking->mentor == $user->id || $user->isModerator()) return view('sweatbook.show', compact('booking', 'positions'));

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
            $booking->start_at = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($booking->date);
            $booking->end_at = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($booking->date);
            $booking->position_id = Position::all()->firstWhere('callsign', strtoupper($data['position']))->id;
            $booking->mentor_notes = $data['mentor_notes'];

            if($booking->start_at->diffInMinutes($booking->end_at, false) <= 0) return back()->withInput()->withErrors('Booking need to have a valid duration!');
            if($booking->start_at->diffInMinutes(Carbon::now(), false) > 0) return back()->withErrors('You cannot create a booking in the past.')->withInput();


            $fullStartDate = Carbon::create($booking->date)->setTime($booking->start_at->format('H'), $booking->start_at->format('i'));
            $fullEndDate = Carbon::create($booking->date)->setTime($booking->end_at->format('H'), $booking->end_at->format('i'));

            if(!Sweatbook::whereBetween('start_at', [$fullStartDate, $fullEndDate])
            ->where('end_at', '!=', $booking->start_at)
            ->where('start_at', '!=', $booking->end_at)
            ->where('position_id', $booking->position_id)
            ->where('id', '!=', $booking->id)
            ->orWhereBetween('end_at', [$booking->start_at, $booking->end_at])
            ->where('end_at', '!=', $booking->start_at)
            ->where('start_at', '!=', $booking->end_at)
            ->where('position_id', $booking->position_id)
            ->where('id', '!=', $booking->id)
            ->get()->isEmpty()) return back()->withErrors('The position is already booked for that time!')->withInput();

            $booking->save();
        }

        return redirect('/sweatbook')->withSuccess("Booking successfully added.");
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
            $booking->start_at = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($booking->date);
            $booking->end_at = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($booking->date);
            $booking->position_id = Position::all()->firstWhere('callsign', strtoupper($data['position']))->id;
            $booking->mentor_notes = $data['mentor_notes'];

            if($booking->start_at->diffInMinutes($booking->end_at, false) <= 0) return back()->withInput()->withErrors('Booking need to have a valid duration!');
            if($booking->start_at->diffInMinutes(Carbon::now(), false) > 0) return back()->withErrors('You cannot create a booking in the past.')->withInput();

            $fullStartDate = Carbon::create($booking->date)->setTime($booking->start_at->format('H'), $booking->start_at->format('i'));
            $fullEndDate = Carbon::create($booking->date)->setTime($booking->end_at->format('H'), $booking->end_at->format('i'));

            if(!Sweatbook::whereBetween('start_at', [$fullStartDate, $fullEndDate])
            ->where('end_at', '!=', $booking->start_at)
            ->where('start_at', '!=', $booking->end_at)
            ->where('position_id', $booking->position_id)
            ->where('id', '!=', $booking->id)
            ->orWhereBetween('end_at', [$booking->start_at, $booking->end_at])
            ->where('end_at', '!=', $booking->start_at)
            ->where('start_at', '!=', $booking->end_at)
            ->where('position_id', $booking->position_id)
            ->where('id', '!=', $booking->id)
            ->get()->isEmpty()) return back()->withErrors('The position is already booked for that time!')->withInput();

            $booking->save();
        }

        return redirect('/sweatbook')->withSuccess("Booking successfully added.");
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

        return redirect('/sweatbook');
    }
}