<?php

namespace App\Http\Controllers;

use App\Position;
use App\Vatbook;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VatbookController extends Controller
{  
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){
        $user = Auth::user();
        $positions = Position::all();
        $bookings = array();
        $feed = file_get_contents("http://vatbook.euroutepro.com/xml2.php");
        $raw = simplexml_load_string($feed)->atcs;

        foreach($raw->children() as $booking){
            if(count($positions->where('callsign', '=', $booking->callsign)) > 0) {
                $position = Position::where('callsign', $booking->callsign)->get()[0];
                array_push($bookings, array($booking, $position->name, $position->fir));
            }
        }
        
        return view('vatbook.calendar', compact('bookings', 'user')); 

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(){
        $user = Auth::user();

        if($user->isMentor()) {
            return view('vatbook.create');
        }
        else {
            abort(404);
        };
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Vatbook $booking
     * @return \Illuminate\Http\Response
     */
    public function show($id){
        $booking = Vatbook::findOrFail($id);
        $user = Auth::user();

        if ($booking->mentor == $user->id || $user->isModerator()) {
            return view('vatbook.show', compact('booking', 'user'));
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
            $booking = new Vatbook();
            
            $booking->mentor = $user->id;
            $booking->date = $date->format('Y-m-d'); 
            $booking->start_at = $data['start_at'];
            $booking->end_at = $data['end_at'];
            $booking->position = $data['position'];
            $booking->mentor_notes = $data['mentor_notes'];

            $booking->save();
        }

        return redirect('/vatbook');
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
        $booking = Vatbook::findOrFail($request->id);

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

        return redirect('/vatbook');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Vatbook  $booking
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $user = Auth::user();
        $booking = Vatbook::findOrFail($id);

        if($user->id == $booking->mentor || $user->isModerator()) {
            $booking->delete();
        }

        return redirect('/vatbook');
    }
}