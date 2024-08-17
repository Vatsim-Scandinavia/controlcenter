<?php

namespace App\Http\Controllers;

use anlutro\LaravelSettings\Facade as Setting;
use App;
use App\Exceptions\VatsimAPIException;
use App\Helpers\TrainingStatus;
use App\Helpers\VatsimRating;
use App\Models\Booking;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller for handling bookings.
 */
class BookingController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get the bookable positions for a user.
     *
     * @return \Illuminate\Support\Collection<Position> Bookable positions
     */
    private function getBookablePositions(User $user)
    {
        // Moderators and above can book any position
        if ($user->isModeratorOrAbove()) {
            return Position::all();
        }

        // Users with a rating of S1 or above can book positions up to their rating
        $positions = new Collection();

        if ($user->rating >= VatsimRating::S1->value) {
            if (Setting::get('atcActivityBasedOnTotalHours')) {
                $positions = Position::where('rating', '<=', $user->rating)->get();
            } else {

                $activeAreas = $user->atcActivity->pluck('area_id');
                $positionsInAreas = Position::whereIn('area_id', $activeAreas)->where('rating', '<=', $user->rating)->get();
                $positions = $positions->merge($positionsInAreas);
            }
        }

        if ($user->getActiveTraining(TrainingStatus::PRE_TRAINING->value)) {
            $positions = $positions->merge(
                $user->getActiveTraining()->area->positions->where('rating', '<=', $user->getActiveTraining()->ratings()->first()->vatsim_rating)
            );
        }

        return $positions;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(User $user)
    {
        $user = Auth::user();
        $this->authorize('view', Booking::class);
        $bookings = Booking::where('deleted', false)->with('user', 'position')->get()->sortBy('time_start');
        $positions = $this->getBookablePositions($user);

        return view('booking.index', compact('bookings', 'user', 'positions'));
    }

    /**
     * Show creation of bulk bookings on booking
     *
     * @return \Illuminate\View\View
     */
    public function bulk()
    {
        $user = Auth::user();
        $this->authorize('create', Booking::class);

        $positions = $this->getBookablePositions($user);

        return view('booking.bulk', compact('user', 'positions'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $booking = Booking::findOrFail($id);
        $user = Auth::user();
        $positions = $this->getBookablePositions($user);
        $this->authorize('update', $booking);

        return view('booking.show', compact('booking', 'user', 'positions'));
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
        $this->authorize('create', Booking::class);

        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'tag' => 'nullable|integer|between:1,3',
        ]);

        $user = Auth::user();
        $booking = new Booking();
        $position = Position::firstWhere('callsign', $data['position']);

        $date = Carbon::createFromFormat('d/m/Y', $data['date']);
        $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
        $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

        $booking->callsign = strtoupper($data['position']);
        $booking->position_id = $position->id;
        $booking->name = $user->name;
        $booking->user_id = $user->id;

        $this->authorize('position', $booking);

        if ($booking->time_start === $booking->time_end) {
            return back()->withErrors('Booking needs to have a valid duration!')->withInput();
        }
        if ($booking->time_start->diffInMinutes($booking->time_end, false) < 0) {
            $booking->time_end->addDay();
        }
        if ($booking->time_start->diffInMinutes(Carbon::now(), false) > 0) {
            return back()->withErrors('You cannot create a booking in the past.')->withInput();
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
            return back()->withErrors('The position is already booked for that time!')->withInput();
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
            $this->authorize('bookTags', $booking);
            switch ($data['tag']) {
                case 1:
                    $booking->exam = 0;
                    $booking->event = 0;
                    $booking->training = 1;
                    $type = 'training';
                    break;
                case 2:
                    $booking->training = 0;
                    $booking->exam = 1;
                    $booking->event = 0;
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
            $url = $this->getVatsimBookingUrl('post');

            try {
                $response = $this->makeHttpRequest($client, $url, 'post', [
                    'callsign' => (string) $booking->callsign,
                    'cid' => $booking->user_id,
                    'type' => $type,
                    'start' => $booking->time_start->format('Y-m-d H:i:s'),
                    'end' => $booking->time_end->format('Y-m-d H:i:s'),
                ]);
            } catch (VatsimAPIException $e) {
                return redirect(route('booking'))->withErrors('Booking failed with error: ' . $e->getMessage() . '. Please contact staff if this issue persists.');
            }

            $vatsim_booking = json_decode($response->getBody()->getContents());

            $booking->vatsim_booking = $vatsim_booking->id;
        }

        $booking->save();

        ActivityLogController::info('BOOKING', 'Created booking booking ' . $booking->id .
        ' ― from ' . Carbon::parse($booking->time_start)->toEuropeanDateTime() .
        ' → ' . Carbon::parse($booking->time_end)->toEuropeanDateTime() .
        ' ― Position: ' . Position::find($booking->position_id)->callsign);

        if ($forcedTrainingTag) {
            return redirect(route('booking'))->withSuccess('Booking successfully added, but training tag was forced due to booking a restricted position.');
        }

        return redirect(route('booking'))->withSuccess('Booking successfully added!');
    }

    /**
     * Store a newly created resource as a bulk
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
        foreach ($positions as $position) {
            $booking = new Booking();

            $date = Carbon::createFromFormat('d/m/Y', $data['date']);
            $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
            $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

            $booking->callsign = strtoupper($position);

            $positionModel = Position::firstWhere('callsign', $position);
            if (isset($positionModel)) {
                $booking->position_id = Position::firstWhere('callsign', $position)->id;
            } else {
                return redirect(route('booking'))->withErrors('The position ' . $position . ' does not exist. The bulk booking stopped here, but previous positions in the list have been booked.')->withInput();
            }

            $booking->name = $user->name;
            $booking->user_id = $user->id;

            $this->authorize('position', $booking);

            if ($booking->time_start === $booking->time_end) {
                return back()->withErrors('Booking needs to have a valid duration!')->withInput();
            }
            if ($booking->time_start->diffInMinutes($booking->time_end, false) < 0) {
                $booking->time_end->addDay();
            }
            if ($booking->time_start->diffInMinutes(Carbon::now(), false) > 0) {
                return back()->withErrors('You cannot create a booking in the past.')->withInput();
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
                return back()->withErrors('The position is already booked for that time!')->withInput();
            }

            $type = null;

            if (isset($data['tag'])) {
                $this->authorize('bookTags', $booking);
                switch ($data['tag']) {
                    case 1:
                        $booking->exam = 0;
                        $booking->event = 0;
                        $booking->training = 1;
                        $type = 'training';
                        break;
                    case 2:
                        $booking->training = 0;
                        $booking->exam = 1;
                        $booking->event = 0;
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
                $url = $this->getVatsimBookingUrl('post');

                try {
                    $response = $this->makeHttpRequest($client, $url, 'post', [
                        'callsign' => (string) $booking->callsign,
                        'cid' => $booking->user_id,
                        'type' => $type,
                        'start' => $booking->time_start->format('Y-m-d H:i:s'),
                        'end' => $booking->time_end->format('Y-m-d H:i:s'),
                    ]);
                } catch (VatsimAPIException $e) {
                    return redirect(route('booking'))->withErrors('Booking failed with error: ' . $e->getMessage() . '. Please contact staff if this issue persists.');
                }

                $vatsim_booking = json_decode($response->getBody()->getContents());

                $booking->vatsim_booking = $vatsim_booking->id;
            }

            $booking->save();

            ActivityLogController::info('BOOKING', 'Created booking BULK booking ' . $booking->id .
            ' ― from ' . Carbon::parse($booking->time_start)->toEuropeanDateTime() .
            ' → ' . Carbon::parse($booking->time_end)->toEuropeanDateTime() .
            ' ― Position: ' . Position::find($booking->position_id)->callsign);
        }

        return redirect(route('booking'))->withSuccess('Bulk bookings successfully added!');
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
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

        $user = Auth::user();
        $booking = Booking::findOrFail($request->id);
        $position = Position::firstWhere('callsign', $data['position']);
        $this->authorize('update', $booking);

        $date = Carbon::createFromFormat('d/m/Y', $data['date']);
        $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
        $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

        $booking->callsign = strtoupper($data['position']);
        $booking->position_id = $position->id;

        $this->authorize('position', $booking);

        if ($booking->time_start === $booking->time_end) {
            return back()->withErrors('Booking needs to have a valid duration!')->withInput();
        }
        if ($booking->time_start->diffInMinutes($booking->time_end, false) < 0) {
            $booking->time_end->addDay();
        }
        if ($booking->time_start->diffInMinutes(Carbon::now(), false) > 0) {
            return back()->withErrors('You cannot create a booking in the past.')->withInput();
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
            return back()->withErrors('The position is already booked for that time!')->withInput();
        }

        $forcedTrainingTag = false;
        $bookingUser = User::find($booking->user_id);

        if (($booking->position->rating > $bookingUser->rating) && ! $bookingUser->isModeratorOrAbove()) {
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
            $this->authorize('bookTags', $booking);
            switch ($data['tag']) {
                case 1:
                    $booking->exam = 0;
                    $booking->event = 0;
                    $booking->training = 1;
                    $type = 'training';
                    break;
                case 2:
                    $booking->training = 0;
                    $booking->exam = 1;
                    $booking->event = 0;
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

            try {
                $response = $this->makeHttpRequest($client, $url, 'put', [
                    'callsign' => (string) $booking->callsign,
                    'cid' => $booking->user_id,
                    'type' => $type,
                    'start' => $booking->time_start->format('Y-m-d H:i:s'),
                    'end' => $booking->time_end->format('Y-m-d H:i:s'),
                ]);
            } catch (VatsimAPIException $e) {
                return redirect(route('booking'))->withErrors('Booking failed with error: ' . $e->getMessage() . '. Please contact staff if this issue persists.');
            }

            $vatsim_booking = json_decode($response->getBody()->getContents());

            $booking->vatsim_booking = $vatsim_booking->id;
        }

        $booking->save();

        ActivityLogController::info('BOOKING', 'Updated booking booking ' . $booking->id .
        ' ― from ' . Carbon::parse($booking->time_start)->toEuropeanDateTime() .
        ' → ' . Carbon::parse($booking->time_end)->toEuropeanDateTime() .
        ' ― Position: ' . Position::find($booking->position_id)->callsign);

        if ($forcedTrainingTag) {
            return redirect(route('booking'))->withSuccess('Booking successfully added, but training tag was forced due to booking a restricted position.');
        }

        return redirect(route('booking'))->withSuccess('Booking successfully added!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Booking  $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($id)
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('update', $booking);

        $booking->deleted = true;

        if (App::environment('production')) {
            $client = new \GuzzleHttp\Client();
            $url = $this->getVatsimBookingUrl('delete', $booking->vatsim_booking);

            try {
                $response = $this->makeHttpRequest($client, $url, 'delete');
            } catch (VatsimAPIException $e) {
                return redirect(route('booking'))->withErrors('Booking deletion failed with error: ' . $e->getMessage() . '. Please contact staff if this issue persists.');
            }
        }

        $booking->save();

        ActivityLogController::warning('BOOKING', 'Deleted booking booking ' . $booking->id .
        ' ― from ' . Carbon::parse($booking->time_start)->toEuropeanDateTime() .
        ' → ' . Carbon::parse($booking->time_end)->toEuropeanDateTime() .
        ' ― Position: ' . Position::find($booking->position_id)->callsign);

        return redirect(route('booking'));
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
                return $client->request('GET', $url, [
                    'headers' => $headers,
                ]);
            } elseif ($type == 'post') {
                return $client->request('POST', $url, ['headers' => $headers, 'form_params' => $data]);
            } elseif ($type == 'put') {
                return $client->request('PUT', $url, ['headers' => $headers, 'form_params' => $data]);
            } elseif ($type == 'delete') {
                return $client->request('DELETE', $url, ['headers' => $headers]);
            }

            return null;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            throw new App\Exceptions\VatsimAPIException($e);
        }
    }
}
