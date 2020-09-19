<?php

namespace App\Http\Controllers;

use App\Position;
use App\Vatbook;
use Carbon\Carbon;
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
        $bookings = Vatbook::where('deleted', false)->get()->sortBy('time_start');
        $positions = Position::all();

        return view('vatbook.index', compact('bookings', 'user', 'positions'));
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
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'training' => 'nullable|numeric|size:1',
            'event' => 'nullable|numeric|size:1'
        ]);

        $user = Auth::user();
        $booking = new Vatbook();

        $date = Carbon::createFromFormat('d/m/Y', $data['date']);
        $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
        $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

        $booking->local_id = floor($user->id / date('z'));
        $booking->callsign = $data['position'];
        $booking->position_id = Position::all()->firstWhere('callsign', strtoupper($data['position']))->id;
        $booking->name = $user->name;
        $booking->cid = $user->id;
        $booking->user_id = $user->id;

        if($booking->time_start->diffInMinutes($booking->time_end, false) <= 0) return back()->withErrors('Booking need to have a valid duration!')->withInput();
        if($booking->time_start->diffInMinutes(Carbon::now(), false) > 0) return back()->withErrors('You cannot create a booking in the past.')->withInput();

        if(!Vatbook::whereBetween('time_start', [$booking->time_start, $booking->time_end])
        ->where('time_end', '!=', $booking->time_start)
        ->where('time_start', '!=', $booking->time_end)
        ->where('position_id', $booking->position_id)
        ->where('deleted', false)
        ->orWhereBetween('time_end', [$booking->time_start, $booking->time_end])
        ->where('time_end', '!=', $booking->time_start)
        ->where('time_start', '!=', $booking->time_end)
        ->where('position_id', $booking->position_id)
        ->where('deleted', false)
        ->get()->isEmpty()) return back()->withErrors('The position is already booked for that time!')->withInput();

        if(isset($data['training']) && isset($data['event'])) return back()->withErrors('Cannot be training and event!')->withInput();

        if(isset($data['training']) && $user->isMentor()) $booking->training = 1;
        else $booking->training = 0;
        if(isset($data['event']) && $user->isModerator()) {
            $eventUrl = "vatsim-scandinavia.org";
            $booking->event = 1;
            $response = file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/insert.asp?Local_URL=noredir&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->name}&Position={$booking->callsign}&sTime={$booking->time_start->format('Hi')}&eTime={$booking->time_end->format('Hi')}&cid={$booking->cid}&T={$booking->training}&E={$booking->event}&E_URL={$eventUrl}&voice=1"));
        }
        else {
            $booking->event = 0;
            $response = file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/insert.asp?Local_URL=noredir&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->name}&Position={$booking->callsign}&sTime={$booking->time_start->format('Hi')}&eTime={$booking->time_end->format('Hi')}&cid={$booking->cid}&T={$booking->training}&E={$booking->event}&voice=1"));
        }

        preg_match_all('/EU_ID=(\d+)/', $response, $matches);

        //dd($response, $matches);

        $booking->eu_id = $matches[1][0];
        $booking->save();
        
        ActivityLogController::info("Created vatbook booking ".$booking->id." from ".$booking->time_start." to ".$booking->time_end." at position id: ".$booking->position_id);
        
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
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'training' => 'nullable|numeric|size:1',
            'event' => 'nullable|numeric|size:1'
        ]);

        $user = Auth::user();
        $booking = Vatbook::findOrFail($request->id);

        if($booking->local_id !== null && $booking->cid == $user->id || $user->isModerator() && $booking->local_id !== null) {
            $date = Carbon::createFromFormat('d/m/Y', $data['date']);
            $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
            $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

            $booking->callsign = $data['position'];
            $booking->position_id = Position::all()->firstWhere('callsign', strtoupper($data['position']))->id;

            if($booking->time_start->diffInMinutes($booking->time_end, false) <= 0) return back()->withErrors('Booking need to have a valid duration!')->withInput();
            if($booking->time_start->diffInMinutes(Carbon::now(), false) > 0) return back()->withErrors('You cannot create a booking in the past.')->withInput();

            if(!Vatbook::whereBetween('time_start', [$booking->time_start, $booking->time_end])
            ->where('time_end', '!=', $booking->time_start)
            ->where('time_start', '!=', $booking->time_end)
            ->where('position_id', $booking->position_id)
            ->where('deleted', false)
            ->where('id', '!=', $booking->id)
            ->orWhereBetween('time_end', [$booking->time_start, $booking->time_end])
            ->where('time_end', '!=', $booking->time_start)
            ->where('time_start', '!=', $booking->time_end)
            ->where('position_id', $booking->position_id)
            ->where('deleted', false)
            ->where('id', '!=', $booking->id)
            ->get()->isEmpty()) return back()->withErrors('The position is already booked for that time!')->withInput();

            if(isset($data['training']) && isset($data['event'])) return back()->withErrors('Cannot be training and event!')->withInput();

            if(isset($data['training']) && $user->isMentor()) $booking->training = 1;
            else $booking->training = 0;
            if(isset($data['event']) && $user->isModerator()) {
                $eventUrl = "vatsim-scandinavia.org";
                $booking->event = 1;
                file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/update.asp?Local_URL=noredir&EU_ID={$booking->eu_id}&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->name}&Position={$booking->callsign}&sTime={$booking->time_start->format('Hi')}&eTime={$booking->time_end->format('Hi')}&cid={$booking->cid}&T={$booking->training}&E={$booking->event}&E_URL={$eventUrl}&voice=1"));
            }
            else {
                $booking->event = 0;
                file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/update.asp?Local_URL=noredir&EU_ID={$booking->eu_id}&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->name}&Position={$booking->callsign}&sTime={$booking->time_start->format('Hi')}&eTime={$booking->time_end->format('Hi')}&cid={$booking->cid}&T={$booking->training}&E={$booking->event}&voice=1"));
            }

            $booking->save();

            ActivityLogController::info("Updated vatbook booking ".$booking->id." from ".$booking->time_start." to ".$booking->time_end." at position id: ".$booking->position_id);
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
            $booking->deleted = true;
            $booking->local_id = null;
            $booking->save();
        }

        ActivityLogController::info("Deleted vatbook booking ".$booking->id." from ".$booking->time_start." to ".$booking->time_end." at position id: ".$booking->position_id);

        return redirect('/vatbook');
    }
}
