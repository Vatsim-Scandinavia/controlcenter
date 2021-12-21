<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vatbook;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VatbookController extends Controller
{
    /*public function __construct()
    {
        $this->middleware('client');
    }*/

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bookings = Vatbook::where('deleted', false)->get()->sortBy('time_start');

        return response()->json([
            'bookings' => $bookings
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $cid, $position)
    {
        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'tag' => 'nullable|integer|between:1,3'
        ]);

        $user = User::findorFail($cid);
        $booking = new Vatbook();
        $date = Carbon::createFromFormat('d/m/Y', $data['date']);
        $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
        $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

        $booking->local_id = floor($user->id / (date('z') + 1));
        $booking->callsign = strtoupper($data['position']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Vatbook  $vatbook
     * @return \Illuminate\Http\Response
     */
    public function show(Vatbook $vatbook)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Vatbook  $vatbook
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Vatbook $vatbook)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Vatbook  $vatbook
     * @return \Illuminate\Http\Response
     */
    public function destroy(Vatbook $vatbook)
    {
        //
    }
}
