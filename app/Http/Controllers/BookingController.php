<?php

namespace App\Http\Controllers;

use App\Exceptions\VatsimAPIException;
use App\Facades\VatsimBookingApi;
use App\Models\Booking;
use App\Models\Position;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller for handling bookings.
 */
class BookingController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private BookingService $bookingService) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(User $user)
    {
        $user = Auth::user();
        $this->authorize('view', Booking::class);
        $bookings = $this->bookingService->getActiveBookings();
        $positions = $this->bookingService->getBookablePositions($user);

        return view('booking.index', compact('bookings', 'user', 'positions'));
    }

    /**
     * Show creation of bulk bookings on booking
     *
     * @return View
     */
    public function bulk()
    {
        $user = Auth::user();
        $this->authorize('create', Booking::class);

        $positions = $this->bookingService->getBookablePositions($user);

        return view('booking.bulk', compact('user', 'positions'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return View
     */
    public function show($id)
    {
        $booking = Booking::findOrFail($id);
        $user = Auth::user();
        $positions = $this->bookingService->getBookablePositions($user);
        $this->authorize('update', $booking);

        return view('booking.show', compact('booking', 'user', 'positions'));
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
        $this->authorize('create', Booking::class);

        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'tag' => 'nullable|integer|between:1,3',
        ]);

        $user = Auth::user();
        $position = Position::firstWhere('callsign', $data['position']);

        $booking = new Booking();
        $this->fillBookingPeriod($booking, $data);
        $booking->callsign = strtoupper($data['position']);
        $booking->position_id = $position->id;
        $booking->name = $user->name;
        $booking->user_id = $user->id;

        $result = $this->persistBooking($booking, $position, $data['tag'] ?? null);
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        if ($result) {
            return redirect(route('booking'))->withSuccess('Booking successfully added, but training tag was forced due to booking a restricted position.');
        }

        return redirect(route('booking'))->withSuccess('Booking successfully added!');
    }

    /**
     * Store a newly created resource as a bulk
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function storeBulk(Request $request)
    {
        $this->authorize('create', Booking::class);

        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'positions' => 'required',
            'tag' => 'nullable|integer|between:1,3',
        ]);

        $user = Auth::user();

        $positions = explode(',', $data['positions']);
        foreach ($positions as $positionCallsign) {
            $position = Position::firstWhere('callsign', $positionCallsign);
            if (! isset($position)) {
                return redirect(route('booking'))->withErrors('The position ' . $positionCallsign . ' does not exist. The bulk booking stopped here, but previous positions in the list have been booked.')->withInput();
            }

            $booking = new Booking();
            $this->fillBookingPeriod($booking, $data);
            $booking->callsign = strtoupper($positionCallsign);
            $booking->position_id = $position->id;
            $booking->name = $user->name;
            $booking->user_id = $user->id;

            $result = $this->persistBooking($booking, $position, $data['tag'] ?? null, bulk: true);
            if ($result instanceof RedirectResponse) {
                return $result;
            }
        }

        return redirect(route('booking'))->withSuccess('Bulk bookings successfully added!');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'tag' => 'nullable|integer|between:1,3',
        ]);

        $booking = Booking::findOrFail($request->id);
        $position = Position::firstWhere('callsign', $data['position']);
        $this->authorize('update', $booking);

        $this->fillBookingPeriod($booking, $data);
        $booking->callsign = strtoupper($data['position']);
        $booking->position_id = $position->id;

        $result = $this->persistBooking($booking, $position, $data['tag'] ?? null);
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        if ($result) {
            return redirect(route('booking'))->withSuccess('Booking successfully added, but training tag was forced due to booking a restricted position.');
        }

        return redirect(route('booking'))->withSuccess('Booking successfully added!');
    }

    /**
     * Set the booking period from the validated request data.
     *
     * @param  array{date: string, start_at: string, end_at: string}  $data
     */
    private function fillBookingPeriod(Booking $booking, array $data): void
    {
        [$startAt, $endAt] = $this->bookingService->parsePeriod($data['date'], $data['start_at'], $data['end_at']);

        $booking->time_start = $startAt;
        $booking->time_end = $endAt;
    }

    /**
     * Validate, publish to the VATSIM booking API and persist the filled booking.
     *
     * @return RedirectResponse|bool An error response, or whether the training tag was forced
     *
     * @throws AuthorizationException
     */
    private function persistBooking(Booking $booking, Position $position, ?int $tag, bool $bulk = false): RedirectResponse|bool
    {
        $this->authorize('position', $booking);

        if ($booking->time_start->eq($booking->time_end)) {
            return back()->withErrors(['duration' => 'Booking needs to have a valid duration!'])->withInput();
        }
        $this->bookingService->adjustForOvernight($booking->time_start, $booking->time_end);
        if ($this->bookingService->isStartInPast($booking->time_start)) {
            return back()->withErrors('You cannot create a booking in the past.')->withInput();
        }

        if ($this->bookingService->bookingConflictExists($booking)) {
            return back()->withErrors('The position is already booked for that time!')->withInput();
        }

        $isNewBooking = ! $booking->exists;

        $forcedTrainingTag = $this->bookingService->shouldForceTrainingTag($booking, $position, $booking->user);
        $booking->training = $forcedTrainingTag ? 1 : 0;

        if ($tag !== null) {
            $this->authorize('bookTags', $booking);
        }
        $type = $this->bookingService->applyTagToBooking($booking, $tag);

        try {
            $vatsimBookingId = $isNewBooking
                ? VatsimBookingApi::createBooking($booking, $type)
                : VatsimBookingApi::updateBooking($booking, $type);
        } catch (VatsimAPIException $e) {
            return redirect(route('booking'))->withErrors('Booking failed with error: ' . $e->getMessage() . '. Please contact staff if this issue persists.');
        }

        if ($vatsimBookingId !== null) {
            $booking->vatsim_booking = $vatsimBookingId;
        }

        $booking->save();

        if ($isNewBooking) {
            $this->bookingService->logBookingCreated($booking, $bulk);
        } else {
            $this->bookingService->logBookingUpdated($booking);
        }

        return $forcedTrainingTag;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return RedirectResponse
     */
    public function delete($id)
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('update', $booking);

        $booking->deleted = true;

        try {
            VatsimBookingApi::deleteBooking($booking);
        } catch (VatsimAPIException $e) {
            return redirect(route('booking'))->withErrors('Booking deletion failed with error: ' . $e->getMessage() . '. Please contact staff if this issue persists.');
        }

        $booking->save();

        $this->bookingService->logBookingDeleted($booking);

        return redirect(route('booking'));
    }
}
