<?php

namespace App\Http\Controllers\API;

use App\Exceptions\VatsimAPIException;
use App\Facades\VatsimBookingApi;
use App\Helpers\LogName;
use App\Helpers\TrainingStatus;
use App\Helpers\VatsimRating;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Position;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\BookingService;
use Carbon\Carbon;
use Closure;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    /**
     * List bookings.
     *
     * Returns a reduced payload when the request is unauthenticated.
     */
    public function index(Request $request): JsonResponse
    {
        $unauthenticatedRequest = $request->attributes->get('unauthenticated');

        if ($unauthenticatedRequest) {
            $bookings = Booking::select(['id', 'callsign', 'time_start', 'time_end', 'training', 'event', 'exam', 'created_at', 'updated_at'])->where('deleted', false)->get()->sortBy('time_start');
        } else {
            $integrationId = $request->attributes->get('api_key')?->integration_id;
            $bookings = Booking::where('deleted', false)
                ->when($integrationId !== null, fn ($query) => $query->where('source', 'EXTERNAL')->where('integration_id', $integrationId))
                ->get()->sortBy('time_start');
        }

        return response()->json(['data' => $bookings->values()], 200);
    }

    /**
     * Create or republish a booking.
     *
     * Validates CID, date, start/end time, position, and optional tag, then persists a
     * new booking, forcing a training tag when the controller or position rating requires it.
     * EXTERNAL assignments with the same integration and external_id update the existing booking.
     */
    #[Response(
        status: 200,
        description: 'The booking was created or republished. `tag` is only present when a training tag was forced.',
        type: 'array{success?: string, message?: string, booking: array{id: int, source: string, integration_id?: string|null, external_id?: string|null, external_revision?: int|null, external_url?: string|null, vatsim_booking: int|null, callsign: string, position_id: int, name: string, time_start: string, time_end: string, user_id: int, training: int, event: int, exam: int, deleted: int, created_at: string, updated_at: string}, tag?: string}',
    )]
    #[Response(
        status: 400,
        description: 'The booking could not be created — for example the start and end time are equal, the start time is in the past, or it overlaps an existing booking.',
        type: 'array{message: string}',
    )]
    #[Response(
        status: 409,
        description: 'The assignment was cancelled, the revision is stale, or a competing mutation holds a lock.',
        type: 'array{message: string}',
    )]
    public function store(Request $request): JsonResponse
    {
        $this->normalizeSource($request);
        $data = $request->validate([
            /**
             * The VATSIM CID (user ID) to create the booking for.
             *
             * @example 10000001
             */
            'cid' => 'required|integer',
            /**
             * The booking date, in `d/m/Y` format.
             *
             * @example 15/02/2024
             */
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            /**
             * The booking start time, in 24-hour `H:i` format.
             *
             * @example 12:00
             */
            'start_at' => 'required|date_format:H:i',
            /**
             * The booking end time, in 24-hour `H:i` format.
             *
             * @example 13:00
             */
            'end_at' => 'required|date_format:H:i',
            /**
             * The callsign of the position to book.
             *
             * @example EKCH_TWR
             */
            'position' => 'required|exists:positions,callsign',
            /**
             * The booking type: `1` for training, `2` for exam, `3` for event.
             *
             * @example 1
             */
            'tag' => 'nullable|integer|between:1,3',
            /**
             * The booking source: CC, VATBOOK, DISCORD, or EXTERNAL.
             *
             * @example CC
             */
            'source' => 'required|string|in:CC,VATBOOK,DISCORD,EXTERNAL',
            /**
             * Stable assignment identifier, required for EXTERNAL bookings. Reuse it when republishing.
             */
            'external_id' => 'required_if:source,EXTERNAL|prohibited_unless:source,EXTERNAL|string|max:128',
            /**
             * Monotonically increasing assignment revision (1–2147483647), required for EXTERNAL bookings.
             */
            'external_revision' => 'required_if:source,EXTERNAL|prohibited_unless:source,EXTERNAL|integer|min:1|max:2147483647',
            /**
             * Optional HTTP or HTTPS link to manage an EXTERNAL booking in the originating system.
             */
            'external_url' => 'nullable|prohibited_unless:source,EXTERNAL|url:http,https|max:2048',
        ]);

        if ($data['source'] !== 'EXTERNAL') {
            return $this->conflictOnLockTimeout(fn () => $this->storeNewBooking($data));
        }

        $integrationId = $request->attributes->get('api_key')?->integration_id;
        abort_unless($integrationId !== null, 403, 'External bookings require an integration API key.');

        return $this->conflictOnLockTimeout(fn () => $this->bookingService->withAssignmentLock($integrationId, $data['external_id'], function () use ($data, $integrationId) {
            $booking = Booking::where('integration_id', $integrationId)
                ->where('external_id', $data['external_id'])->first();

            if ($booking === null) {
                return $this->storeNewBooking($data, $integrationId);
            }

            if ($booking->deleted) {
                return response()->json(['message' => 'This external assignment has been cancelled. Use a new external_id to create a new assignment.'], 409);
            }

            return $this->updateLocked($booking, $data);
        }));
    }

    /**
     * @param  array{cid: int, date: string, start_at: string, end_at: string, position: string, source: string, tag?: int|null, external_id?: string, external_revision?: int, external_url?: string|null}  $data
     */
    private function storeNewBooking(array $data, ?string $integrationId = null): JsonResponse
    {
        $position = Position::firstWhere('callsign', $data['position']);

        return $this->bookingService->withPositionLock($position->id, fn () => $this->persistNewBooking($data, $position, $integrationId));
    }

    /**
     * @param  array{cid: int, date: string, start_at: string, end_at: string, position: string, source: string, tag?: int|null, external_id?: string, external_revision?: int, external_url?: string|null}  $data
     */
    private function persistNewBooking(array $data, Position $position, ?string $integrationId): JsonResponse
    {
        $user = User::findorFail($data['cid']);
        $booking = new Booking();

        $date = Carbon::createFromFormat('d/m/Y', $data['date']);
        $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
        $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

        $booking->callsign = strtoupper($data['position']);
        $booking->position_id = $position->id;
        $booking->name = $user->name;
        $booking->user_id = $user->id;
        $booking->source = strtoupper($data['source']);
        if ($booking->source === 'EXTERNAL') {
            $booking->integration_id = $integrationId;
            $booking->external_id = $data['external_id'];
            $booking->external_revision = $data['external_revision'];
            $booking->external_url = $data['external_url'] ?? null;
        }

        if (Carbon::parse($booking->time_start)->eq(Carbon::parse($booking->time_end))) {
            return response()->json([
                'message' => 'Start and end time cannot be the same',
            ], 400);
        }

        if ($booking->time_start->diffInMinutes($booking->time_end, false) < 0) {
            $booking->time_end->addDay();
        }

        if ($booking->time_start->diffInMinutes(Carbon::now(), false) > 0) {
            return response()->json([
                'message' => 'Start time cannot be in the past',
            ], 400);
        }

        if ($this->bookingService->bookingConflictExists($booking)) {
            return response()->json([
                'message' => 'Booking overlaps with existing booking',
            ], 400);
        }

        $forcedTrainingTag = false;

        if ($booking->position->rating->isGreaterThan($user->rating) && ! $user->hasPermission('bookings.bypass-restrictions')) {
            $booking->training = 1;
            $forcedTrainingTag = true;
        } elseif ($position->requiredRating && ! $user->hasEndorsementRating($position->requiredRating) && ! $user->hasPermission('bookings.bypass-restrictions')) {
            $booking->training = 1;
            $forcedTrainingTag = true;
        } else {
            $booking->training = 0;
        }

        $type = null;

        if (isset($data['tag'])) {
            switch ($data['tag']) {
                case 1:
                    $booking->exam = 0;
                    $booking->event = 0;
                    $booking->training = 1;
                    $type = 'training';
                    break;
                case 2:
                    $booking->exam = 1;
                    $booking->event = 0;
                    $booking->training = 0;
                    $type = 'exam';
                    break;
                case 3:
                    $booking->exam = 0;
                    $booking->event = 1;
                    $booking->training = 0;
                    $type = 'event';
                    break;
            }
        } else {
            $booking->exam = 0;
            $booking->event = 0;
            $type = 'booking';
        }

        try {
            $vatsimBookingId = VatsimBookingApi::createBooking($booking, $type);
        } catch (VatsimAPIException $e) {
            return response()->json([
                'message' => 'VATSIM API error: ' . $e->getMessage(),
            ], 400);
        }

        if ($vatsimBookingId !== null) {
            $booking->vatsim_booking = $vatsimBookingId;
        }

        $booking->save();

        ActivityLogService::info(LogName::Booking, 'Created booking booking' . $booking->id . ' via API' .
            ' ― from ' . Carbon::parse($booking->time_start)->toEuropeanDateTime() .
            ' → ' . Carbon::parse($booking->time_end)->toEuropeanDateTime() .
            ' ― Position: ' . Position::find($booking->position_id)->callsign);

        if ($forcedTrainingTag) {
            return response()->json([
                'success' => 'Booking created',
                'booking' => $booking,
                'tag' => 'Training',
            ], 200);
        }

        return response()->json([
            'success' => 'Booking created',
            'booking' => $booking,
        ], 200);
    }

    /**
     * Display a single booking.
     */
    public function show(Booking $booking): JsonResponse
    {
        $user = User::findorFail($booking->user_id);
        $positions = new Collection();
        if ($user->rating->isGreaterThanOrEqual(VatsimRating::S2)) {
            $positions = Position::where('rating', '<=', $user->rating)->get();
        }

        if ($user->getActiveTraining(TrainingStatus::PRE_TRAINING)) {
            $training = $user->getActiveTraining(TrainingStatus::PRE_TRAINING);
            $highestRating = $training->getHighestVatsimRating()?->vatsim_rating;
            $positions = $positions->merge(
                $training->area->positions->filter(
                    fn (Position $position) => $highestRating !== null && $position->rating->isLessThanOrEqual($highestRating)
                )
            );
        }

        if ($user->hasPermission('bookings.bypass-restrictions')) {
            $positions = Position::all();
        }

        return response()->json([
            'booking' => $booking,
        ], 200);
    }

    /**
     * Update an existing booking.
     *
     * Revalidates CID, date, start/end time, position, and optional tag, re-checks for
     * overlapping bookings, and re-evaluates whether a training tag must be forced.
     */
    #[Response(
        status: 200,
        description: 'The booking was updated, or the same revision had already been published.',
        type: 'array{message: string, booking: \App\Models\Booking, tag?: string}',
    )]
    #[Response(
        status: 409,
        description: 'The booking was cancelled, the revision is stale, or a competing mutation holds a lock.',
        type: 'array{message: string}',
    )]
    public function update(Request $request, Booking $booking): JsonResponse
    {
        $this->normalizeSource($request);
        $data = $request->validate([
            /**
             * The VATSIM CID (user ID) the booking belongs to.
             *
             * @example 10000001
             */
            'cid' => 'required|integer',
            /**
             * The booking date, in `d/m/Y` format.
             *
             * @example 15/02/2024
             */
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            /**
             * The booking start time, in 24-hour `H:i` format.
             *
             * @example 12:00
             */
            'start_at' => 'required|date_format:H:i',
            /**
             * The booking end time, in 24-hour `H:i` format.
             *
             * @example 13:00
             */
            'end_at' => 'required|date_format:H:i',
            /**
             * The callsign of the position to book.
             *
             * @example EKCH_TWR
             */
            'position' => 'required|exists:positions,callsign',
            /**
             * The booking type: `1` for training, `2` for exam, `3` for event.
             *
             * @example 1
             */
            'tag' => 'nullable|integer|between:1,3',
            'external_url' => 'nullable|url:http,https|max:2048',
            'external_id' => 'sometimes|string|max:128',
            /**
             * Required for EXTERNAL bookings. Increase this revision for every assignment change.
             */
            'external_revision' => $this->revisionRules($booking),
            'source' => 'sometimes|string|in:CC,VATBOOK,DISCORD,EXTERNAL',
        ]);

        return $this->conflictOnLockTimeout(fn () => $this->bookingService->withBookingLock($booking, fn (Booking $current) => $this->updateLocked($current, $data)));
    }

    private function updateLocked(Booking $booking, array $data): JsonResponse
    {
        if ($booking->deleted) {
            return response()->json(['message' => 'This booking has been cancelled.'], 409);
        }

        foreach (['source', 'external_id'] as $attribute) {
            if (array_key_exists($attribute, $data) && $data[$attribute] !== $booking->$attribute) {
                throw ValidationException::withMessages([$attribute => 'The booking source and assignment identifier cannot be changed.']);
            }
        }

        if ($booking->source === 'EXTERNAL') {
            $revision = (int) $data['external_revision'];
            if ($revision < $booking->external_revision) {
                return response()->json(['message' => 'This assignment revision is older than the stored revision.'], 409);
            }
            if ($revision === $booking->external_revision) {
                return response()->json(['message' => 'Booking already published', 'booking' => $booking], 200);
            }
        }

        $user = User::findorFail($data['cid']);
        $position = Position::firstWhere('callsign', $data['position']);

        return $this->bookingService->withPositionLock($position->id, fn () => $this->persistUpdatedBooking($booking, $position, $user, $data));
    }

    private function persistUpdatedBooking(Booking $booking, Position $position, User $user, array $data): JsonResponse
    {
        $date = Carbon::createFromFormat('d/m/Y', $data['date']);
        $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
        $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

        $booking->callsign = strtoupper($data['position']);
        $booking->position_id = $position->id;
        $booking->user_id = $user->id;
        $booking->name = $user->name;
        if ($booking->source === 'EXTERNAL') {
            $booking->external_revision = $data['external_revision'];
            $booking->external_url = array_key_exists('external_url', $data) ? $data['external_url'] : $booking->external_url;
        }

        if (Carbon::parse($booking->time_start)->eq(Carbon::parse($booking->time_end))) {
            return response()->json([
                'message' => 'Booking needs to have a valid duration!',
            ], 400);
        }

        if ($booking->time_start->diffInMinutes($booking->time_end, false) < 0) {
            $booking->time_end->addDay();
        }

        if ($booking->time_start->diffInMinutes(Carbon::now(), false) > 0) {
            return response()->json([
                'message' => 'You cannot create a booking in the past.',
            ], 400);
        }

        if ($this->bookingService->bookingConflictExists($booking)) {
            return response()->json([
                'message' => 'The position is already booked for that time!',
            ], 400);
        }

        $forcedTrainingTag = false;

        if ($booking->position->rating->isGreaterThan($user->rating) && ! $user->hasPermission('bookings.bypass-restrictions')) {
            $booking->training = 1;
            $forcedTrainingTag = true;
        } elseif ($position->requiredRating && ! $user->hasEndorsementRating($position->requiredRating) && ! $user->hasPermission('bookings.bypass-restrictions')) {
            $booking->training = 1;
            $forcedTrainingTag = true;
        } else {
            $booking->training = 0;
        }

        $type = null;

        if (isset($data['tag'])) {
            switch ($data['tag']) {
                case 1:
                    $booking->exam = 0;
                    $booking->event = 0;
                    $booking->training = 1;
                    $type = 'training';
                    break;
                case 2:
                    $booking->exam = 1;
                    $booking->event = 0;
                    $booking->training = 0;
                    $type = 'exam';
                    break;
                case 3:
                    $booking->training = 0;
                    $booking->exam = 0;
                    $booking->event = 1;
                    $type = 'event';
                    break;
            }
        } else {
            $booking->exam = 0;
            $booking->event = 0;
            $type = 'booking';
        }

        try {
            $vatsimBookingId = VatsimBookingApi::updateBooking($booking, $type);
        } catch (VatsimAPIException $e) {
            return response()->json([
                'message' => 'VATSIM API error: ' . $e->getMessage(),
            ], 400);
        }

        if ($vatsimBookingId !== null) {
            $booking->vatsim_booking = $vatsimBookingId;
        }

        $booking->save();

        ActivityLogService::info(LogName::Booking, 'Updated booking booking ' . $booking->id . ' via API' .
            ' ― from ' . Carbon::parse($booking->time_start)->toEuropeanDateTime() .
            ' → ' . Carbon::parse($booking->time_end)->toEuropeanDateTime() .
            ' ― Position: ' . Position::find($booking->position_id)->callsign);

        if ($forcedTrainingTag) {
            return response()->json([
                'message' => 'Booking updated',
                'booking' => $booking,
                'tag' => 'Training',
            ], 200);
        }

        return response()->json([
            'message' => 'Booking updated',
            'booking' => $booking,
        ], 200);
    }

    /**
     * Delete a booking.
     *
     * Soft-deletes the booking and cancels the corresponding VATSIM booking.
     */
    #[Response(
        status: 200,
        description: 'The booking was cancelled or had already been cancelled.',
        type: 'array{message: string, booking: \App\Models\Booking}',
    )]
    #[Response(
        status: 409,
        description: 'The cancellation revision is stale, or a competing mutation holds a lock.',
        type: 'array{message: string}',
    )]
    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate([
            /**
             * Required for EXTERNAL bookings. Must be newer than the last accepted publication or update.
             */
            'external_revision' => $this->revisionRules($booking),
        ]);

        return $this->conflictOnLockTimeout(fn () => $this->bookingService->withBookingLock($booking, fn (Booking $current) => $this->cancelLocked($current, $data)));
    }

    private function cancelLocked(Booking $booking, array $data): JsonResponse
    {
        if ($booking->deleted) {
            return response()->json(['message' => 'Booking already deleted', 'booking' => $booking], 200);
        }

        if ($booking->source === 'EXTERNAL') {
            if ((int) $data['external_revision'] <= $booking->external_revision) {
                return response()->json(['message' => 'Cancellation requires a newer assignment revision.'], 409);
            }
            $booking->external_revision = $data['external_revision'];
        }

        $booking->deleted = true;

        try {
            VatsimBookingApi::deleteBooking($booking);
        } catch (VatsimAPIException $e) {
            // Thrown rather than returned so the documented 200 response keeps its precise shape.
            throw new HttpResponseException(response()->json([
                'message' => 'VATSIM API error: ' . $e->getMessage(),
            ], 400));
        }

        $booking->save();

        ActivityLogService::warning(LogName::Booking, 'Deleted booking booking ' . $booking->id . ' via API' .
            ' ― from ' . Carbon::parse($booking->time_start)->toEuropeanDateTime() .
            ' → ' . Carbon::parse($booking->time_end)->toEuropeanDateTime() .
            ' ― Position: ' . Position::find($booking->position_id)->callsign);

        return response()->json([
            'message' => 'Booking deleted',
            'booking' => $booking,
        ], 200);
    }

    private function conflictOnLockTimeout(Closure $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'This booking or position is being changed. Retry shortly.'], 409);
        }
    }

    private function normalizeSource(Request $request): void
    {
        if (is_string($request->input('source'))) {
            $request->merge(['source' => strtoupper($request->input('source'))]);
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function revisionRules(Booking $booking): array
    {
        $isExternal = $booking->source === 'EXTERNAL';

        return [Rule::requiredIf($isExternal), Rule::prohibitedIf(! $isExternal), 'integer', 'min:1', 'max:2147483647'];
    }
}
