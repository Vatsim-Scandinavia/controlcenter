<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App;
use App\Helpers\Vatsim;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class UpdateAtcHours extends Command
{
    private $base_api_url = 'https://api.vatsim.net/api/ratings/';

    private $qualificationPeriod; // Period to sum up by. In months.

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:atc:hours {user?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the ATC hours for eligible members';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Fetch settings
        $this->qualificationPeriod = Setting::get('atcActivityQualificationPeriod', 12);
        $this->info('Starting ATC update...');

        // Fetch members
        $optionalUserIdFilter = $this->argument('user');
        $ratedMembers = User::getRatedMembers($optionalUserIdFilter);
        $members = User::whereIn('id', $ratedMembers->pluck('id'))->get();

        // Update member hours
        $this->updateMemberATCHours($members);
    }

    /**
     * Update ATC active hours in database
     *
     * @param  Collection<User>  $members
     * @return null
     */
    private function updateMemberATCHours(Collection $members)
    {
        $this->info('Fetching seen ATC positions...');

        // Get callsigns per area
        $divisionCallsignPrefixes = collect();
        foreach (Area::all() as $area) {
            $divisionCallsignPrefixes[$area->id] = $area->positions->pluck('callsign')->map(function ($callsign) {
                return substr($callsign, 0, 4);
            })->unique();
        }

        $this->info('Updating member ATC hours...');

        foreach ($members as $member) {
            $client = new \GuzzleHttp\Client();
            if (App::environment('production')) {
                $url = $this->getQueryString($member->id);
            } else {
                $url = 'https://api.vatsim.net/api/ratings/1352906/atcsessions/?start=2021-12-30';
            }
            $response = $this->makeHttpGetRequest($client, $url);

            if ($response == null) {
                Log::error('updateMemberATCHours: Failed to fetch GuzzleHttp Response, url: ' . $url);

                continue;
            } elseif ($response->getStatusCode() >= 300) {
                Log::warning('updateMemberATCHours: User ' . $member->id . ' fetch failed with code ' . $response->getStatusCode());

                continue;
            }

            try {
                $parsedData = json_decode($response->getBody()->getContents(), false, JSON_THROW_ON_ERROR);
            } catch (\Exception $e) {
                Log::error('updateMemberATCHours: Failed to parse JSON for member: ' . $member->id . ': ' . $e);

                continue;
            }

            $this->updateHoursForMember($member, collect($parsedData->results), $divisionCallsignPrefixes);
        }
    }

    /**
     * Check if active members should keep their active status
     *
     * @param  Collection<string>  $divisionCallsignPrefixes
     * @return null
     */
    private function updateHoursForMember(User $member, Collection $sessions, Collection $divisionCallsignPrefixes)
    {
        $this->info('Updating ATC hours for member: ' . $member->id);

        foreach (Area::all() as $area) {
            $hoursActiveInArea = $sessions
                ->filter(fn ($session) => Vatsim::isDivisionCallsign($session->callsign, $divisionCallsignPrefixes[$area->id]))
                ->map(function ($session) {
                    return floatval($session->minutes_on_callsign);
                })
                ->sum()
                / 60;

            if (! App::environment('production')) {
                $this->info('Updating ATC hours for member: ' . $member->id . ' in area: ' . $area->id . ' to: ' . $hoursActiveInArea . ' hours');
            }

            // Update in database or create if not found
            try {
                $activity = AtcActivity::where('user_id', $member->id)->where('area_id', $area->id)->firstOrFail();
                $activity->hours = $hoursActiveInArea;
                $activity->save();
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                if ($hoursActiveInArea > 0) {
                    AtcActivity::create([
                        'user_id' => $member->id,
                        'area_id' => $area->id,
                        'hours' => $hoursActiveInArea,
                    ]);
                }
            }
        }
    }

    /**
     * Make HTTP GET request
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    private function makeHttpGetRequest(\GuzzleHttp\Client $client, string $url)
    {
        try {
            $response = $client->get($url);
        } catch (\GuzzleHttp\Exception\GuzzleException $exception) {
            Log::error(
                'Hit exception while updating atc_active. URL was: ' . $url .
                    '. HTTP status code was: ' . $exception->getCode() .
                    "\n" .
                    $exception->getTraceAsString()
            );
        }

        if (isset($response)) {
            return $response;
        }

        return null;
    }

    /**
     * Get the query string for the http call
     *
     * @return string
     */
    private function getQueryString(int $user_id)
    {
        $query_string = $this->base_api_url . $user_id;
        $query_string .= '/atcsessions/';
        $query_string .= '?start=' . Carbon::now()->subMonths($this->qualificationPeriod)->format('Y-m-d');

        return $query_string;
    }
}
