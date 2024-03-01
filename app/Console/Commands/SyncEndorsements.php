<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Models\User;
use Illuminate\Console\Command;
use App\Models\Endorsement;
use App\Models\Rating;

class SyncRoster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:endorsements {rating_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync the endorsements with Division API';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        if (! Setting::get('divisionApiEnabled')) {
            $this->error('This command is only available when reactivation setting is enabled');

            return Command::FAILURE;
        }

        //
        // ** NOTE **
        // ** EXPERIMENTAL FUNCTION **
        //
        // This command is only meant to be ran manually at this point. Though it's a nice baseline for future sync functionalities
        // and for migration to the division api integration
        //
        //

        $this->info('Syncing endorsements with Division API...');

        $rating = Rating::find($this->argument('rating_id'));
        $tier = $rating->endorsement_type;

        if(!isset($tier)){
            $this->error('Rating not found or not a tiered rating');
            return Command::FAILURE;
        }

        $rosterResponse = DivisionApi::getTierEndorsements(substr($tier, -1));
        if ($rosterResponse && $rosterResponse->successful()) {
        
            $apiEndorsements = collect($rosterResponse->json()['data']);
            $apiEndorsements = $apiEndorsements->where('position', $rating->name);

            $storedEndorsements = Endorsement::where('type', 'MASC')->whereHas('ratings', function ($query) use ($rating) {
                $query->where('id', $rating->id);
            })->with('ratings')->get();

            $storedEndorsements->each(function ($storedEndorsement) use ($apiEndorsements, $rating) {
                $apiEndorsement = $apiEndorsements->where('user_cid', $storedEndorsement->user_id)->first();
                if ($apiEndorsement) {
                    $this->info('Endorsement for ' . $storedEndorsement->user_id . ' already exists in Division API. SKIPPING...');
                } else {
                    $this->warn('Endorsement for ' . $storedEndorsement->user_id . ' does not exist in Division API. ADDING...');
                    $response = DivisionApi::assignTierEndorsement(User::find($storedEndorsement->user_id), $rating, 1352906);
                    if ($response->successful()) {
                        $this->info('Added endorsement for ' . $storedEndorsement->user_id . ' to Division API.');
                    } else {
                        $this->error('Failed to add endorsement for ' . $storedEndorsement->user_id . ' to Division API: ' . $response->json()['message']);
                    }
                }
            });


        } else {
            $this->error('Failed to fetch endorsements from Division API');
            return Command::FAILURE;
        }

    }
}
