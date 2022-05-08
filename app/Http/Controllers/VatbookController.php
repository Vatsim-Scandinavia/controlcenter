<?php

namespace App\Http\Controllers;

use App;
use App\Models\User;
use App\Models\Position;
use App\Models\Vatbook;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use anlutro\LaravelSettings\Facade as Setting;

/**
 * Controller for handling Vatbook/vRoute bookings.
 */
class VatbookController extends Controller
{

    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View
     */
    public function index(User $user){
        $user = Auth::user();
        $this->authorize('view', Vatbook::class);
        $bookings = Vatbook::where('deleted', false)->get()->sortBy('time_start');
        $positions = new Collection();
        if($user->rating >= 3) $positions = Position::where('rating', '<=', $user->rating)->get();
        if($user->getActiveTraining(1)) $positions = $positions->merge($user->getActiveTraining()->area->positions->where('rating', '<=', $user->getActiveTraining()->ratings()->first()->vatsim_rating));
        if($user->isModeratorOrAbove()) $positions = Position::all();

        return view('vatbook.index', compact('bookings', 'user', 'positions'));
    }

    /**
     * Show creation of bulk bookings on Vatbook
     *
     * @return \Illuminate\View\View
     */
    public function bulk(){
        $user = Auth::user();
        $this->authorize('create', Vatbook::class);

        $positions = Position::all();
        return view('vatbook.bulk', compact('user', 'positions'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Vatbook $booking
     * @return \Illuminate\View\View
     */
    public function show($id){
        $booking = Vatbook::findOrFail($id);
        $user = Auth::user();
        $positions = new Collection();
        if($user->rating >= 3) $positions = Position::where('rating', '<=', $user->rating)->get();
        if($user->getActiveTraining(1)) $positions = $positions->merge($user->getActiveTraining()->area->positions->where('rating', '<=', $user->getActiveTraining()->ratings()->first()->vatsim_rating));
        if($user->isModeratorOrAbove()) $positions = Position::all();
        $this->authorize('update', $booking);

        return view('vatbook.show', compact('booking', 'user', 'positions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', Vatbook::class);

        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'tag' => 'nullable|integer|between:1,3'
        ]);

        $user = Auth::user();
        $booking = new Vatbook();

        $date = Carbon::createFromFormat('d/m/Y', $data['date']);
        $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
        $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

        $booking->local_id = floor($user->id / (date('z') + 1));
        $booking->callsign = strtoupper($data['position']);
        $booking->position_id = Position::all()->firstWhere('callsign', strtoupper($data['position']))->id;
        $booking->name = $user->name;
        $booking->cid = $user->id;
        $booking->user_id = $user->id;

        $this->authorize('position', $booking);

        if($booking->time_start === $booking->time_end) return back()->withErrors('Booking needs to have a valid duration!')->withInput();
        if($booking->time_start->diffInMinutes($booking->time_end, false) < 0) $booking->time_end->addDay();
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

        $forcedTrainingTag = false;

        if(($booking->position->rating > $user->rating || $user->rating < 3) && !$user->isModeratorOrAbove()){
            $booking->training = 1;
            $forcedTrainingTag = true;
        } else if($user->getActiveTraining() && $user->getActiveTraining()->isMaeTraining() && $booking->position->rating > $user->rating && $booking->position->mae) {
            $booking->training = 1;
            $forcedTrainingTag = true;
        } else {
            $booking->training = 0;
        }

        $type = null;

        if(isset($data['tag'])) {
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

        if(App::environment('production')) {
            if($booking->event) {
                $eventUrl = Setting::get('linkDomain');
                $response = file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/insert.asp?Local_URL=noredir&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->cid}&Position={$booking->callsign}&sTime={$booking->time_start->format('Hi')}&eTime={$booking->time_end->format('Hi')}&cid={$booking->cid}&T={$booking->training}&E={$booking->event}&E_URL={$eventUrl}&voice=1"));
            }
            else {
                $response = file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/insert.asp?Local_URL=noredir&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->cid}&Position={$booking->callsign}&sTime={$booking->time_start->format('Hi')}&eTime={$booking->time_end->format('Hi')}&cid={$booking->cid}&T={$booking->training}&E={$booking->event}&voice=1"));
            }

            preg_match_all('/EU_ID=(\d+)/', $response, $matches);
            $booking->eu_id = $matches[1][0];
        } else {
            $booking->eu_id = 0;
        }

        if(App::environment('production')) {
            $client = new \GuzzleHttp\Client();

            $url = $this->getVatsimBookingUrl('post');
            $response = $this->makeHttpRequest($client, $url, 'post', [
                'callsign' => $booking->callsign,
                'cid' => $booking->cid,
                'type' => $type,
                'start' => $booking->time_start->format('Y-m-d H:i:s'),
                'end' => $booking->time_end->format('Y-m-d H:i:s'),
            ]);

            $vatsim_booking = json_decode($response->getBody()->getContents());

            $booking->vatsim_booking = $vatsim_booking->id;
        }
        $booking->save();

        ActivityLogController::info('BOOKING', "Created vatbook booking ".$booking->id.
        " ― from ".Carbon::parse($booking->time_start)->toEuropeanDateTime().
        " → ".Carbon::parse($booking->time_end)->toEuropeanDateTime().
        " ― Position: ".Position::find($booking->position_id)->callsign);

        if($forcedTrainingTag){
            return redirect(route('vatbook'))->withSuccess('Booking successfully added, but training tag was forced due to booking a restricted position.');
        }

        return redirect(route('vatbook'))->withSuccess('Booking successfully added!');
    }

    /**
     * Store a newly created resource as a bulk
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeBulk(Request $request)
    {
        $this->authorize('create', Vatbook::class);

        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'positions' => 'required',
            'tag' => 'nullable|integer|between:1,3'
        ]);

        $user = Auth::user();

        $positions = explode(',', $data['positions']);
        foreach($positions as $position){
            $booking = new Vatbook();

            $date = Carbon::createFromFormat('d/m/Y', $data['date']);
            $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
            $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

            $booking->local_id = floor($user->id / (date('z') + 1));
            $booking->callsign = strtoupper($position);
            $booking->position_id = Position::all()->firstWhere('callsign', strtoupper($position))->id;
            $booking->name = $user->name;
            $booking->cid = $user->id;
            $booking->user_id = $user->id;

            $this->authorize('position', $booking);

            if($booking->time_start === $booking->time_end) return back()->withErrors('Booking needs to have a valid duration!')->withInput();
            if($booking->time_start->diffInMinutes($booking->time_end, false) < 0) $booking->time_end->addDay();
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

            $type = null;

            if(isset($data['tag'])) {
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

            if(App::environment('production')) {
                if($booking->event) {
                    $eventUrl = Setting::get('linkDomain');
                    $response = file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/insert.asp?Local_URL=noredir&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->cid}&Position={$booking->callsign}&sTime={$booking->time_start->format('Hi')}&eTime={$booking->time_end->format('Hi')}&cid={$booking->cid}&T={$booking->training}&E={$booking->event}&E_URL={$eventUrl}&voice=1"));
                }
                else {
                    $response = file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/insert.asp?Local_URL=noredir&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->cid}&Position={$booking->callsign}&sTime={$booking->time_start->format('Hi')}&eTime={$booking->time_end->format('Hi')}&cid={$booking->cid}&T={$booking->training}&E={$booking->event}&voice=1"));
                }

                preg_match_all('/EU_ID=(\d+)/', $response, $matches);
                $booking->eu_id = $matches[1][0];
            } else {
                $booking->eu_id = 0;
            }

            if(App::environment('production')) {
                $client = new \GuzzleHttp\Client();

                $url = $this->getVatsimBookingUrl('post');
                $response = $this->makeHttpRequest($client, $url, 'post', [
                    'callsign' => $booking->callsign,
                    'cid' => $booking->cid,
                    'type' => $type,
                    'start' => $booking->time_start->format('Y-m-d H:i:s'),
                    'end' => $booking->time_end->format('Y-m-d H:i:s'),
                ]);

                $vatsim_booking = json_decode($response->getBody()->getContents());

                $booking->vatsim_booking = $vatsim_booking->id;
            }
            $booking->save();

            ActivityLogController::info('BOOKING', "Created vatbook BULK booking ".$booking->id.
            " ― from ".Carbon::parse($booking->time_start)->toEuropeanDateTime().
            " → ".Carbon::parse($booking->time_end)->toEuropeanDateTime().
            " ― Position: ".Position::find($booking->position_id)->callsign);
        }

        return redirect(route('vatbook'))->withSuccess('Bulk bookings successfully added!');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function update(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date_format:d/m/Y|after_or_equal:today',
            'start_at' => 'required|date_format:H:i',
            'end_at' => 'required|date_format:H:i',
            'position' => 'required|exists:positions,callsign',
            'tag' => 'nullable|integer|between:1,3'
        ]);

        $user = Auth::user();
        $booking = Vatbook::findOrFail($request->id);
        $this->authorize('update', $booking);

        $date = Carbon::createFromFormat('d/m/Y', $data['date']);
        $booking->time_start = Carbon::createFromFormat('H:i', $data['start_at'])->setDateFrom($date);
        $booking->time_end = Carbon::createFromFormat('H:i', $data['end_at'])->setDateFrom($date);

        $booking->callsign = strtoupper($data['position']);
        $booking->position_id = Position::all()->firstWhere('callsign', strtoupper($data['position']))->id;

        $this->authorize('position', $booking);

        if($booking->time_start === $booking->time_end) return back()->withErrors('Booking needs to have a valid duration!')->withInput();
        if($booking->time_start->diffInMinutes($booking->time_end, false) < 0) $booking->time_end->addDay();
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

        $forcedTrainingTag = false;

        if(($booking->position->rating > User::find($booking->user_id)->rating || User::find($booking->user_id)->rating < 3) && !$user->isModeratorOrAbove()){
            $booking->training = 1;
            $forcedTrainingTag = true;
        } else if($user->getActiveTraining() && $user->getActiveTraining()->isMaeTraining() && $booking->position->mae == true) {
            $booking->training = 1;
            $forcedTrainingTag = true;
        } else {
            $booking->training = 0;
        }

        $type = null;

        if(isset($data['tag'])) {
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

        if(App::environment('production')) {
            if($booking->event) {
                $eventUrl = Setting::get('linkDomain');
                file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/update.asp?Local_URL=noredir&EU_ID={$booking->eu_id}&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->cid}&Position={$booking->callsign}&sTime={$booking->time_start->format('Hi')}&eTime={$booking->time_end->format('Hi')}&cid={$booking->cid}&T={$booking->training}&E={$booking->event}&E_URL={$eventUrl}&voice=1"));
            }
            else {
                file_get_contents(str_replace(' ', '%20',"http://vatbook.euroutepro.com/atc/update.asp?Local_URL=noredir&EU_ID={$booking->eu_id}&Local_ID={$booking->local_id}&b_day={$date->format('d')}&b_month={$date->format('m')}&b_year={$date->format('Y')}&Controller={$booking->cid}&Position={$booking->callsign}&sTime={$booking->time_start->format('Hi')}&eTime={$booking->time_end->format('Hi')}&cid={$booking->cid}&T={$booking->training}&E={$booking->event}&voice=1"));
            }
        }

        if(App::environment('production')) {
            $client = new \GuzzleHttp\Client();
            $url = $this->getVatsimBookingUrl('put', $booking->vatsim_booking);
            $response = $this->makeHttpRequest($client, $url, 'put', [
                'callsign' => $booking->callsign,
                'cid' => $booking->cid,
                'type' => $type,
                'start' => $booking->time_start->format('Y-m-d H:i:s'),
                'end' => $booking->time_end->format('Y-m-d H:i:s'),
            ]);

            $vatsim_booking = json_decode($response->getBody()->getContents());

            $booking->vatsim_booking = $vatsim_booking->id;
        }
        $booking->save();

        ActivityLogController::info('BOOKING', "Updated vatbook booking ".$booking->id.
        " ― from ".Carbon::parse($booking->time_start)->toEuropeanDateTime().
        " → ".Carbon::parse($booking->time_end)->toEuropeanDateTime().
        " ― Position: ".Position::find($booking->position_id)->callsign);

        if($forcedTrainingTag){
            return redirect(route('vatbook'))->withSuccess('Booking successfully added, but training tag was forced due to booking a restricted position.');
        }

        return redirect(route('vatbook'))->withSuccess('Booking successfully added!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Vatbook  $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete($id)
    {
        $booking = Vatbook::findOrFail($id);
        $this->authorize('update', $booking);

        if(App::environment('production')) {
            file_get_contents('http://vatbook.euroutepro.com/atc/delete.asp?Local_URL=noredir&EU_ID=' . $booking->eu_id . '&Local_ID=' . $booking->local_id);
        }
        $booking->deleted = true;
        $booking->local_id = null;

        if(App::environment('production')) {
            $client = new \GuzzleHttp\Client();
            $url = $this->getVatsimBookingUrl('delete', $booking->vatsim_booking);
            $response = $this->makeHttpRequest($client, $url, 'delete');
        }

        $booking->save();

        ActivityLogController::warning('BOOKING', "Deleted vatbook booking ".$booking->id.
        " ― from ".Carbon::parse($booking->time_start)->toEuropeanDateTime().
        " → ".Carbon::parse($booking->time_end)->toEuropeanDateTime().
        " ― Position: ".Position::find($booking->position_id)->callsign);

        return redirect(route('vatbook'));
    }

    private function getVatsimBookingUrl(string $type, int $id = null)
    {
        if($type == 'get' || $type == 'post') {
            $url = Config::get('vatsim.booking_api_url') . '/booking';
        } elseif ($type == 'put' || $type == 'delete') {
            $url = Config::get('vatsim.booking_api_url') . '/booking/' . $id;
        } else {
            return null;
        }
        return $url;
    }

    private function makeHttpRequest(\GuzzleHttp\Client $client, string $url, string $type, array $data = null) {
        try {
            $headers = [
                'Authorization' => 'Bearer ' . Config::get('vatsim.booking_api_token'),        
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
            return redirect(route('vatbook'))->withErrors('VATSIM API error: ' . $e->getMessage());
        }

        if(isset($response))
            return $response;

        return null;
    }
}
