<?php

namespace App\Http\Controllers;

use App\Booking;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SweatboxController extends Controller
{  
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){
        $user = Auth::user();
        $bookings = Booking::all();

        if($user->isMentor()) {
            return view('sweatbox.calendar', compact('bookings', 'user'));
        }
        else {
            abort(404);
        };
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(){
        $user = Auth::user();

        if($user->isMentor()) {
            return view('sweatbox.create');
        }
        else {
            abort(404);
        };
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Booking $booking
     * @return \Illuminate\Http\Response
     */
    public function show($id){
        $booking = Booking::findOrFail($id);
        $user = Auth::user();

        if ($booking->mentor == $user->id || $user->isModerator()) {
            return view('sweatbox.show', compact('booking', 'user'));
        }
        else {
            abort(404);
        };
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
            'date' => 'required|date:Y-m-d',
            'start_at' => 'required',
            'end_at' => 'required',
            'position' => 'required|max:30',
            'mentor_notes' => 'nullable|max:255'
        ]);

        $user = Auth::user();

        if($user->isMentor() || $user->isModerator()) {
            $date = new DateTime($data['date']);
            $booking = new Booking();
            
            $booking->mentor = $user->id;
            $booking->date = $date->format('Y-m-d'); 
            $booking->start_at = $data['start_at'];
            $booking->end_at = $data['end_at'];
            $booking->position = $data['position'];
            $booking->mentor_notes = $data['mentor_notes'];

            $booking->save();
        }

        return redirect('/sweatbox');
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
            'date' => 'required|date:Y-m-d',
            'start_at' => 'required',
            'end_at' => 'required',
            'position' => 'required|max:30',
            'mentor_notes' => 'nullable|max:255'
        ]);

        $user = Auth::user();
        $booking = Booking::findOrFail($request->id);

        if($user->id == $booking->mentor || $user->isModerator()) {
            $date = new DateTime($data['date']);

            $booking->mentor = $booking->mentor;
            $booking->date = $date->format('Y-m-d'); 
            $booking->start_at = $data['start_at'];
            $booking->end_at = $data['end_at'];
            $booking->position = $data['position'];
            $booking->mentor_notes = $data['mentor_notes'];

            $booking->save();
        }

        return redirect('/sweatbox');
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
        $booking = Booking::findOrFail($id);

        if($user->id == $booking->mentor || $user->isModerator()) {
            $booking->delete();
        }

        return redirect('/sweatbox');
    }
}