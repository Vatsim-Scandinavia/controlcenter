<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App;
use App\Models\Handover;
use App\Models\AtcActivity;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use VatsimRating;

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
        $this->info("Starting ATC update...");

        // Fetch members
        $optionalUserIdFilter = $this->argument('user');
        $members = Handover::getRatedMembers($optionalUserIdFilter);

        // Update member hours
        $this->updateMemberATCHours($members);
    }

    /**
     * Update ATC active hours in database
     *
     * @param Collection<Handover> $members
     * @return null
     */
    private function updateMemberATCHours(Collection $members)
    {
        $this->info("Fetching seen ATC positions...");
        $divisionCallsignPrefixes = collect(DB::select(
            DB::raw('SELECT DISTINCT LEFT(callsign, 4) as prefix FROM positions;')
        ));

        $this->info("Updating member ATC hours...");

        foreach ($members as $member) {

            $client = new \GuzzleHttp\Client();
            if(App::environment('production')) {
                $url = $this->getQueryString($member->id);
            } else {
                $url = "https://api.vatsim.net/api/ratings/1352906/atcsessions/?start=2021-12-30";
            }
            $response = $this->makeHttpGetRequest($client, $url);

            $this->info("Checking " . $member->id);

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
                Log::error("updateMemberATCHours: Failed to parse JSON for member: " . $member->id . ": " . $e);
                continue;
            }

            $this->updateHoursForMember($member, collect($parsedData->results), $divisionCallsignPrefixes);
        }
    }

    /**
     * Check if active members should keep their active status
     *
     * @param Handover $member
     * @param Collection $sessions
     * @param Collection<string> $divisionCallsignPrefixes
     * @return null
     */
    private function updateHoursForMember(Handover $member, Collection $sessions, Collection $divisionCallsignPrefixes)
    {
        $this->info("Updating ATC hours for member: " . $member->id);

        $isDivisionCallsign = function ($session) use ($divisionCallsignPrefixes) {
            try {
                isDivisionCallsign($session->callsign, $divisionCallsignPrefixes);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return false;
            }
            return true;
        };
        $hoursActiveInDivision = $sessions
            ->filter($isDivisionCallsign)
            ->map(function ($session) {
                return floatval($session->minutes_on_callsign);
            })
            ->sum()
            / 60;

        // Update in database or create if not found
        try {
            $activity = AtcActivity::findOrFail($member->id);
            $activity->hours = $hoursActiveInDivision;
            $activity->save();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            AtcActivity::create([
                'user_id' => $member->id,
                'hours' => $hoursActiveInDivision
            ]);
        }
    }

    /**
     * Check if active members should keep their active status
     *
     * @param 
     * @return null
     */
    private function checkActiveMembers()
    {

    }


    /**
     * Make HTTP GET request
     *
     * @param \GuzzleHttp\Client $client
     * @param string $url
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

        if (isset($response))
            return $response;

        return null;
    }


    /**
     * Get the query string for the http call
     *
     * @param int $user_id
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
