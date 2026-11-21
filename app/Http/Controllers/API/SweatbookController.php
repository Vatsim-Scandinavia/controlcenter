<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\Sweatbook;
use App\Services\BookingService;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SweatbookController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    /**
     * List active sweatbox bookings.
     */
    #[Response(status: 200, description: 'Active sweatbox bookings.')]
    public function index(): JsonResponse
    {
        $bookings = $this->bookingService->getActiveBookings(Sweatbook::class);

        return response()->json([
            'data' => $bookings->map(fn (Sweatbook $booking) => $this->mapBooking($booking))->values(),
        ], 200);
    }

    /**
     * Show a sweatbox booking.
     */
    #[Response(status: 200, description: 'A single sweatbox booking.')]
    public function show(Sweatbook $sweatbook): JsonResponse
    {
        $sweatbook->loadMissing(['position', 'user']);

        return response()->json(['data' => $this->mapBooking($sweatbook)], 200);
    }

    /**
     * Create a sweatbox booking.
     */
    #[Response(status: 201, description: 'The sweatbox booking was created.')]
    #[Response(status: 409, description: 'The booking overlaps another booking or starts in the past.')]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cid' => 'required|integer|exists:users,id',
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'mentor_notes' => 'nullable|string|max:255',
        ]);

        $booking = new Sweatbook;
        $booking->user_id = $data['cid'];

        return $this->persist($booking, $data, created: true);
    }

    /**
     * Update a sweatbox booking.
     */
    #[Response(status: 200, description: 'The sweatbox booking was updated.')]
    #[Response(status: 409, description: 'The booking overlaps another booking or starts in the past.')]
    public function update(Request $request, Sweatbook $sweatbook): JsonResponse
    {
        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'mentor_notes' => 'nullable|string|max:255',
        ]);

        return $this->persist($sweatbook, $data, created: false);
    }

    /**
     * Delete a sweatbox booking.
     */
    #[Response(status: 200, description: 'The sweatbox booking was deleted.')]
    public function destroy(Sweatbook $sweatbook): JsonResponse
    {
        $this->bookingService->logBookingDeleted($sweatbook);
        $id = $sweatbook->id;
        $sweatbook->delete();

        return response()->json(['data' => ['id' => $id]], 200);
    }

    /**
     * @param  array{date: string, start_at: string, end_at: string, position: string, mentor_notes?: ?string, cid?: int}  $data
     */
    private function persist(Sweatbook $booking, array $data, bool $created): JsonResponse
    {
        [$startAt, $endAt] = $this->bookingService->parsePeriod($data['date'], $data['start_at'], $data['end_at']);
        $this->bookingService->adjustForOvernight($startAt, $endAt);

        $booking->time_start = $startAt;
        $booking->time_end = $endAt;
        $booking->position_id = Position::firstWhere('callsign', $data['position'])->id;
        $booking->mentor_notes = $data['mentor_notes'] ?? null;

        if ($startAt->diffInMinutes($endAt, false) <= 0) {
            return response()->json(['message' => 'Booking need to have a valid duration!'], 409);
        }

        if ($this->bookingService->isStartInPast($startAt)) {
            return response()->json(['message' => 'You cannot create a booking in the past.'], 409);
        }

        if ($this->bookingService->sweatbookConflictExists($booking)) {
            return response()->json(['message' => 'The position is already booked for that time!'], 409);
        }

        $booking->save();

        if ($created) {
            $this->bookingService->logBookingCreated($booking);
        } else {
            $this->bookingService->logBookingUpdated($booking);
        }

        return response()->json(['data' => $this->mapBooking($booking->fresh(['position', 'user']))], $created ? 201 : 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBooking(Sweatbook $booking): array
    {
        $start = $booking->time_start;
        $end = $booking->time_end;

        return [
            'id' => $booking->id,
            'user_id' => $booking->user_id,
            'position_id' => $booking->position_id,
            'callsign' => $booking->position?->callsign,
            'time_start' => is_object($start) && method_exists($start, 'toIso8601String') ? $start->toIso8601String() : (string) $start,
            'time_end' => is_object($end) && method_exists($end, 'toIso8601String') ? $end->toIso8601String() : (string) $end,
            'mentor_notes' => $booking->mentor_notes,
        ];
    }
}
