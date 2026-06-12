<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Sweatbook;
use App\Services\BookingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller for sweatbox bookings
 */
class SweatbookController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private BookingService $bookingService) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     *
     * @throws AuthorizationException
     */
    public function index()
    {
        $user = Auth::user();
        $this->authorize('view', Sweatbook::class);
        $bookings = $this->bookingService->getActiveBookings(Sweatbook::class);
        $positions = Position::all();

        return view('sweatbook.index', compact('bookings', 'user', 'positions'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return View
     *
     * @throws AuthorizationException
     */
    public function show($id)
    {
        $booking = Sweatbook::findOrFail($id);
        $positions = Position::all();
        $this->authorize('update', $booking);

        return view('sweatbook.show', compact('booking', 'positions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
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

        $booking = new Sweatbook();
        $booking->user_id = Auth::id();

        return $this->saveBooking($booking, $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
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

        return $this->saveBooking($booking, $data);
    }

    /**
     * Fill, validate and persist the booking from the validated request data.
     *
     * @param  array{date: string, start_at: string, end_at: string, position: string, mentor_notes?: ?string}  $data
     */
    private function saveBooking(Sweatbook $booking, array $data): RedirectResponse
    {
        $isNewBooking = ! $booking->exists;

        [$startAt, $endAt] = $this->bookingService->parsePeriod($data['date'], $data['start_at'], $data['end_at']);

        $booking->time_start = $startAt;
        $booking->time_end = $endAt;
        $booking->position_id = Position::firstWhere('callsign', $data['position'])->id;
        $booking->mentor_notes = $data['mentor_notes'] ?? null;

        if ($startAt->diffInMinutes($endAt, false) <= 0) {
            return back()->withInput()->withErrors('Booking need to have a valid duration!');
        }
        if ($this->bookingService->isStartInPast($startAt)) {
            return back()->withErrors('You cannot create a booking in the past.')->withInput();
        }

        if ($this->bookingService->sweatbookConflictExists($booking)) {
            return back()->withErrors('The position is already booked for that time!')->withInput();
        }

        $booking->save();

        if ($isNewBooking) {
            $this->bookingService->logBookingCreated($booking);

            return redirect('/sweatbook')->withSuccess('Booking successfully saved.');
        }

        $this->bookingService->logBookingUpdated($booking);

        return redirect('/sweatbook')->withSuccess('Booking successfully edited.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function delete($id)
    {
        $booking = Sweatbook::findOrFail($id);
        $this->authorize('update', $booking);

        $this->bookingService->logBookingDeleted($booking);

        $booking->delete();

        return redirect('/sweatbook');
    }
}
