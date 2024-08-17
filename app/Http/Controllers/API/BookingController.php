<?php

namespace App\Http\Controllers\API;

use App;
use App\Helpers\TrainingStatus;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BookingController extends Controller
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
    public function index(Request $request)
    {
        $unauthenticatedRequest = $request->attributes->get('unauthenticated');

        if ($unauthenticatedRequest) {
            $bookings = Booking::select(['id', 'callsign', 'time_start', 'time_end', 'training', 'event', 'exam', 'created_at', 'updated_at'])->where('deleted', false)->get()->sortBy('time_start');
        } else {
            $bookings = Booking::where('deleted', false)->get()->sortBy('time_start');
        }

        return response()->json(['data' => $bookings->values()], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'cid' => 'required|integer',
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'tag' => 'nullable|integer|between:1,3',
            'source' => 'required',
        ]);

        $user = User::findorFail($request['cid']);
        $booking = new Booking();
        $position = Position::firstWhere('callsign', $data['position']);

        $date = Carbon::createFromFormat('d/m/Y', $data['date']);
        $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
        $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

        $booking->callsign = strtoupper($data['position']);
        $booking->position_id = $position->id;
        $booking->name = $user->name;
        $booking->user_id = $user->id;
        $booking->source = strtoupper($data['source']);

        if ($booking->time_start === $booking->time_end) {
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

        if (! Booking::whereBetween('time_start', [$booking->time_start, $booking->time_end])
            ->where('time_end', '!=', $booking->time_start)
            ->where('time_start', '!=', $booking->time_end)
            ->where('position_id', $booking->position_id)
            ->where('deleted', false)
            ->orWhereBetween('time_end', [$booking->time_start, $booking->time_end])
            ->where('time_end', '!=', $booking->time_start)
            ->where('time_start', '!=', $booking->time_end)
            ->where('position_id', $booking->position_id)
            ->where('deleted', false)
            ->get()->isEmpty()) {
            return response()->json([
                'message' => 'Booking overlaps with existing booking',
            ], 400);
        }

        $forcedTrainingTag = false;

        if (($booking->position->rating > $user->rating) && ! $user->isModeratorOrAbove()) {
            $booking->training = 1;
            $forcedTrainingTag = true;
        } elseif ($position->requiredRating && ! $user->hasEndorsementRating($position->requiredRating) && ! $user->isModeratorOrAbove()) {
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

        if (App::environment('production')) {
            $client = new \GuzzleHttp\Client();

            $url = $this->getVatsimBookingUrl('post');
            $response = $this->makeHttpRequest($client, $url, 'post', [
                'callsign' => (string) $booking->callsign,
                'cid' => $booking->user_id,
                'type' => $type,
                'start' => $booking->time_start->format('Y-m-d H:i:s'),
                'end' => $booking->time_end->format('Y-m-d H:i:s'),
            ]);

            $vatsim_booking = json_decode($response->getBody()->getContents());

            $booking->vatsim_booking = $vatsim_booking->id;
        }

        $booking->save();

        ActivityLogController::info('BOOKING', 'Created booking booking' . $booking->id . ' via API' .
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
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(booking $booking)
    {
        $user = User::findorFail($booking->user_id);
        $positions = new Collection();
        if ($user->rating >= 3) {
            $positions = Position::where('rating', '<=', $user->rating)->get();
        }

        if ($user->getActiveTraining(TrainingStatus::PRE_TRAINING->value)) {
            $positions = $positions->merge($user->getActiveTraining()->area->positions->where('rating', '<=', $user->getActiveTraining()->first()->vatsim_rating));
        }

        if ($user->isModeratorOrAbove()) {
            $positions = Position::all();
        }

        return response()->json([
            'booking' => $booking,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, booking $booking)
    {
        $data = $request->validate([
            'cid' => 'required|integer',
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'tag' => 'nullable|integer|between:1,3',
        ]);

        $user = User::findorFail($data['cid']);
        $position = Position::firstWhere('callsign', $data['position']);

        $date = Carbon::createFromFormat('d/m/Y', $data['date']);
        $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
        $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

        $booking->callsign = strtoupper($data['position']);
        $booking->position_id = $position->id;

        if ($booking->time_start === $booking->time_end) {
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

        if (! Booking::whereBetween('time_start', [$booking->time_start, $booking->time_end])
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
            ->get()->isEmpty()) {
            return response()->json([
                'message' => 'The position is already booked for that time!',
            ], 400);
        }

        $forcedTrainingTag = false;

        if (($booking->position->rating > $user->rating) && ! $user->isModeratorOrAbove()) {
            $booking->training = 1;
            $forcedTrainingTag = true;
        } elseif ($position->requiredRating && ! $user->hasEndorsementRating($position->requiredRating) && ! $user->isModeratorOrAbove()) {
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

        if (App::environment('production')) {
            $client = new \GuzzleHttp\Client();
            $url = $this->getVatsimBookingUrl('put', $booking->vatsim_booking);
            $response = $this->makeHttpRequest($client, $url, 'put', [
                'callsign' => (string) $booking->callsign,
                'cid' => $booking->user_id,
                'type' => $type,
                'start' => $booking->time_start->format('Y-m-d H:i:s'),
                'end' => $booking->time_end->format('Y-m-d H:i:s'),
            ]);

            $vatsim_booking = json_decode($response->getBody()->getContents());

            $booking->vatsim_booking = $vatsim_booking->id;
        }

        $booking->save();

        ActivityLogController::info('BOOKING', 'Updated booking booking ' . $booking->id . ' via API' .
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
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(booking $booking)
    {
        $booking->deleted = true;
        $client = new \GuzzleHttp\Client();
        $url = $this->getVatsimBookingUrl('delete', $booking->vatsim_booking);
        $response = $this->makeHttpRequest($client, $url, 'delete');

        $booking->save();

        ActivityLogController::warning('BOOKING', 'Deleted booking booking ' . $booking->id . ' via API' .
            ' ― from ' . Carbon::parse($booking->time_start)->toEuropeanDateTime() .
            ' → ' . Carbon::parse($booking->time_end)->toEuropeanDateTime() .
            ' ― Position: ' . Position::find($booking->position_id)->callsign);

        return response()->json([
            'message' => 'Booking deleted',
            'booking' => $booking,
        ], 200);
    }

    private function getVatsimBookingUrl(string $type, ?int $id = null)
    {
        if ($type == 'get' || $type == 'post') {
            $url = config('vatsim.booking_api_url') . '/booking';
        } elseif ($type == 'put' || $type == 'delete') {
            $url = config('vatsim.booking_api_url') . '/booking/' . $id;
        } else {
            return null;
        }

        return $url;
    }

    private function makeHttpRequest(\GuzzleHttp\Client $client, string $url, string $type, ?array $data = null)
    {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . config('vatsim.booking_api_token'),
                'Accept' => 'application/json',
            ];
            if ($type == 'get') {
                $response = $client->request('GET', $url, [
                    'headers' => $headers,
                ]);
            } elseif ($type == 'post') {
                $response = $client->request('POST', $url, ['headers' => $headers, 'form_params' => $data]);
            } elseif ($type == 'put') {
                $response = $client->request('PUT', $url, ['headers' => $headers, 'form_params' => $data]);
            } elseif ($type == 'delete') {
                $response = $client->request('DELETE', $url, ['headers' => $headers]);
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return response()->json([
                'message' => 'VATSIM API error: ' . $e->getMessage(),
            ], 400);
        }

        if (isset($response)) {
            return $response;
        }

        return null;
    }
}
