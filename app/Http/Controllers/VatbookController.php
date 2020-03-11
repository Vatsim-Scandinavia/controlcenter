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
        $bookings = Vatbook::all();

        return view('vatbook.calendar', compact('bookings', 'user')); 
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(){
        $user = Auth::user();
        $positions = Position::all();

        return view('vatbook.create', compact('positions', 'user'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Vatbook $booking
     * @return \Illuminate\Http\Response
     */
    public function show($id){
        $booking = Vatbook::findOrFail($id);
        $positions = Position::all();
        $user = Auth::user();

        if ($booking->local_id !== null && $booking->cid == $user->id || $user->isModerator() && $booking->local_id !== null) return view('vatbook.show', compact('booking', 'positions', 'user'));

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
            'date' => 'required|date:Y-m-d',
            'start_at' => 'required|regex:/^\d{2}:\d{2}$/',
            'end_at' => 'required|regex:/^\d{2}:\d{2}$/',
            'position' => 'required|exists:positions,callsign',
        ]);

        $user = Auth::user();
        $date = new DateTime($data['date']);
        $start_at = new DateTime($data['start_at']);
        $end_at = new DateTime($data['end_at']);
        $booking = new Vatbook();
        
        $booking->local_id = floor($user->id / date('z'));
        $booking->callsign = $data['position'];
        $booking->position_id = Position::all()->firstWhere('callsign', $data['position'])->id;
        $booking->name = "{$user->handover->firstName} {$user->handover->lastName}";
        $booking->time_start = date('Y-m-d H:i:s', strtotime($data['date'] . $data['start_at']));
        $booking->time_end = date('Y-m-d H:i:s', strtotime($data['date'] . $data['end_at']));
        $booking->cid = $user->id;
        $booking->user_id = $user->id;
        $booking->training = 0;
        $booking->event = 0;

        $response = file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/insert.asp?Local_URL=noredir&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->name}&Position={$booking->callsign}&sTime={$start_at->format('Hi')}&eTime={$end_at->format('Hi')}&cid={$booking->cid}&T=0&E=0&voice=1"));

        preg_match_all('/EU_ID=(\d+)/', $response, $matches);
        
        $booking->eu_id = $matches[1][0];
        $booking->save();

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
            'start_at' => 'required|regex:/^\d{2}:\d{2}$/',
            'end_at' => 'required|regex:/^\d{2}:\d{2}$/',
            'position' => 'required|exists:positions,callsign',
        ]);

        $user = Auth::user();
        $booking = Vatbook::findOrFail($request->id);

        if($booking->local_id !== null && $booking->cid == $user->id || $user->isModerator() && $booking->local_id !== null) {
            $date = new DateTime($data['date']);
            $start_at = new DateTime($data['start_at']);
            $end_at = new DateTime($data['end_at']);
            
            file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/update.asp?Local_URL=noredir&EU_ID={$booking->eu_id}&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->name}&Position={$booking->callsign}&sTime={$start_at->format('Hi')}&eTime={$end_at->format('Hi')}&cid={$booking->cid}&T=0&E=0&voice=1"));

            $booking->callsign = $data['position'];
            $booking->position_id = Position::all()->firstWhere('callsign', $data['position'])->id;
            $booking->time_start = date('Y-m-d H:i:s', strtotime($data['date'] . $data['start_at']));
            $booking->time_end = date('Y-m-d H:i:s', strtotime($data['date'] . $data['end_at']));

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

        if($booking->local_id !== null && $user->id == $booking->cid || $user->isModerator() && $booking->local_id !== null) {
            file_get_contents('http://vatbook.euroutepro.com/atc/delete.asp?Local_URL=noredir&EU_ID=' . $booking->eu_id . '&Local_ID=' . $booking->local_id);
            $booking->delete();
        }

        return redirect('/vatbook');
    }
}