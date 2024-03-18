<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Sweatbook;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for sweatbox bookings
 */
class SweatbookController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $user = Auth::user();
        $this->authorize('view', Sweatbook::class);
        $bookings = Sweatbook::with('user', 'position')->get()->sortBy('date')->sortBy('start_at');
        $positions = Position::all();

        return view('sweatbook.index', compact('bookings', 'user', 'positions'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show($id)
    {
        $booking = Sweatbook::findOrFail($id);
        $positions = Position::all();
        $user = Auth::user();
        $this->authorize('update', $booking);

        return view('sweatbook.show', compact('booking', 'positions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', Sweatbook::class);

        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'mentor_notes' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        $date = Carbon::createFromFormat('d/m/Y', $data['date']);
        $booking = new Sweatbook();

        $booking->user_id = $user->id;
        $booking->date = $date->format('Y-m-d');
        $booking->start_at = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($booking->date);
        $booking->end_at = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($booking->date);
        $booking->position_id = Position::firstWhere('callsign', $data['position'])->id;
        $booking->mentor_notes = $data['mentor_notes'];

        if ($booking->start_at === $booking->end_at) {
            return back()->withInput()->withErrors('Booking need to have a valid duration!');
        }
        if ($booking->start_at->diffInMinutes(Carbon::now(), false) > 0) {
            return back()->withErrors('You cannot create a booking in the past.')->withInput();
        }

        if (! Sweatbook::whereBetween('start_at', [$booking->start_at, $booking->end_at])
            ->where('date', $booking->date)
            ->where('end_at', '!=', $booking->start_at)
            ->where('start_at', '!=', $booking->end_at)
            ->where('position_id', $booking->position_id)
            ->where('id', '!=', $booking->id)
            ->orWhereBetween('end_at', [$booking->start_at, $booking->end_at])
            ->where('date', $booking->date)
            ->where('end_at', '!=', $booking->start_at)
            ->where('start_at', '!=', $booking->end_at)
            ->where('position_id', $booking->position_id)
            ->where('id', '!=', $booking->id)
            ->get()->isEmpty()) {
            return back()->withErrors('The position is already booked for that time!')->withInput();
        }

        $booking->save();

        ActivityLogController::info('BOOKING', 'Created sweatbox booking ' . $booking->id .
        ' ― from ' . Carbon::parse($booking->start_at)->toEuropeanDateTime() .
        ' → ' . Carbon::parse($booking->end_at)->toEuropeanDateTime() .
        ' ― Position: ' . Position::find($booking->position_id)->callsign);

        return redirect('/sweatbook')->withSuccess('Booking successfully saved.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'mentor_notes' => 'nullable|string|max:255',
        ]);

        $booking = Sweatbook::findOrFail($request->id);
        $this->authorize('update', $booking);

        $date = Carbon::createFromFormat('d/m/Y', $data['date']);

        $booking->user_id = $booking->user_id;
        $booking->date = $date->format('Y-m-d');
        $booking->start_at = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($booking->date);
        $booking->end_at = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($booking->date);
        $booking->position_id = Position::firstWhere('callsign', $data['position'])->id;
        $booking->mentor_notes = $data['mentor_notes'];

        if ($booking->start_at->diffInMinutes($booking->end_at, false) <= 0) {
            return back()->withInput()->withErrors('Booking need to have a valid duration!');
        }
        if ($booking->start_at->diffInMinutes(Carbon::now(), false) > 0) {
            return back()->withErrors('You cannot create a booking in the past.')->withInput();
        }

        $fullStartDate = Carbon::create($booking->date)->setTime($booking->start_at->format('H'), $booking->start_at->format('i'));
        $fullEndDate = Carbon::create($booking->date)->setTime($booking->end_at->format('H'), $booking->end_at->format('i'));

        if (! Sweatbook::whereBetween('start_at', [$fullStartDate, $fullEndDate])
            ->where('date', $booking->date)
            ->where('end_at', '!=', $booking->start_at)
            ->where('start_at', '!=', $booking->end_at)
            ->where('position_id', $booking->position_id)
            ->where('id', '!=', $booking->id)
            ->orWhereBetween('end_at', [$booking->start_at, $booking->end_at])
            ->where('date', $booking->date)
            ->where('end_at', '!=', $booking->start_at)
            ->where('start_at', '!=', $booking->end_at)
            ->where('position_id', $booking->position_id)
            ->where('id', '!=', $booking->id)
            ->get()->isEmpty()) {
            return back()->withErrors('The position is already booked for that time!')->withInput();
        }

        $booking->save();

        ActivityLogController::info('BOOKING', 'Updated sweatbox booking ' . $booking->id .
        ' ― from ' . Carbon::parse($booking->start_at)->toEuropeanDateTime() .
        ' → ' . Carbon::parse($booking->end_at)->toEuropeanDateTime() .
        ' ― Position: ' . Position::find($booking->position_id)->callsign);

        return redirect('/sweatbook')->withSuccess('Booking successfully edited.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete($id)
    {
        $user = Auth::user();
        $booking = Sweatbook::findOrFail($id);
        $this->authorize('update', $booking);

        ActivityLogController::warning('BOOKING', 'Deleted sweatbox booking ' . $booking->id .
        ' ― from ' . Carbon::parse($booking->start_at)->toEuropeanDateTime() .
        ' → ' . Carbon::parse($booking->end_at)->toEuropeanDateTime() .
        ' ― Position: ' . Position::find($booking->position_id)->callsign);

        $booking->delete();

        return redirect('/sweatbook');
    }
}
