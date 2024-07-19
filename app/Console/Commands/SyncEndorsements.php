<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Models\Endorsement;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Console\Command;

class SyncEndorsements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:endorsements {addEndorsementsAsUser?} {--dry-run}';

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
            $this->error('This command is only available when Division API setting is enabled');

            return Command::FAILURE;
        }

        $addEndorsementsAsUser = $this->argument('addEndorsementsAsUser');
        $dryRun = $this->option('dry-run');

        if (! $dryRun && isset($addEndorsementsAsUser) && ! $this->confirm('Are you sure you want to add missing endorsements in API as VATSIM ID ' . $addEndorsementsAsUser . '?')) {
            $this->error('Aborted.');

            return Command::SUCCESS;
        }

        $this->info('Syncing endorsements with Division API...');
        $tieredRatings = Rating::whereIn('endorsement_type', ['T1', 'T2'])->get();

        $tier1Roster = DivisionApi::getTierEndorsements(1);
        $tier2Roster = DivisionApi::getTierEndorsements(2);

        // Loop through all T1 and T2 ratings
        foreach ($tieredRatings as $rating) {

            $tier = $rating->endorsement_type;
            $this->info('[' . $rating->endorsement_type . ' ' . $rating->name . ']');

            // Select the correct roster
            if ($tier === 'T1') {
                $rosterResponse = $tier1Roster;
            } elseif ($tier === 'T2') {
                $rosterResponse = $tier2Roster;
            }

            // If roster is fetched successfully
            if ($rosterResponse && $rosterResponse->successful()) {

                // Endorsements stored in API
                $apiEndorsements = collect($rosterResponse->json()['data']);
                $apiEndorsements = $apiEndorsements->where('position', strtoupper($rating->name));

                // Relevant users we want to display endorsements of
                $relevantActiveUsers = User::getActiveAtcMembers()->filter(function ($user) use ($rating) {
                    // Only keep users who are active in Rating's area
                    return $user->isAtcActive($rating->areas->first());
                });

                $relevantVisitingUsers = User::whereHas('endorsements', function ($query) {
                    $query->where('type', 'VISITING')->where('revoked', false)->where('expired', false);
                })->get();

                $relevantUsers = $relevantActiveUsers->merge($relevantVisitingUsers);

                // Endorsements stored in CC
                $storedEndorsements = Endorsement::where('type', 'FACILITY')
                    ->where('revoked', false)
                    ->where('expired', false)
                    ->whereIn('user_id', $relevantUsers->pluck('id'))
                    ->whereHas('ratings', function ($query) use ($rating) {
                        $query->where('id', $rating->id);
                    })
                    ->with('ratings')
                    ->get();

                // Add endorsements which don't exist in Division API
                if (isset($addEndorsementsAsUser)) {
                    $storedEndorsements->each(function ($storedEndorsement) use ($apiEndorsements, $rating, $addEndorsementsAsUser, $dryRun) {
                        $apiEndorsement = $apiEndorsements->where('user_cid', $storedEndorsement->user_id)->first();
                        if (! $apiEndorsement) {
                            $this->warn('Endorsement for ' . $storedEndorsement->user_id . ' is not stored in API. Adding...' . ($dryRun ? ' (dry run)' : ''));

                            if (! $dryRun) {
                                $response = DivisionApi::assignTierEndorsement(User::find($storedEndorsement->user_id), $rating, $addEndorsementsAsUser);
                                if ($response->failed()) {
                                    $this->error('Failed to add endorsement for ' . $storedEndorsement->user_id . ' to Division API: ' . $response->json()['message']);
                                }
                            }
                        }
                    });
                }

                // Remove endorsements which don't exist in CC
                $apiEndorsements->each(function ($apiEndorsement) use ($storedEndorsements, $tier, $dryRun) {
                    $storedEndorsement = $storedEndorsements->where('user_id', $apiEndorsement['user_cid'])->first();
                    if (! $storedEndorsement) {
                        $this->warn('Endorsement for ' . $apiEndorsement['user_cid'] . ' should not be stored in API. Removing...' . ($dryRun ? ' (dry run)' : ''));

                        if (! $dryRun) {
                            $response = DivisionApi::revokeTierEndorsement($tier, $apiEndorsement['user_cid'], $apiEndorsement['position']);
                            if ($response->failed()) {
                                $this->error('Failed to remove endorsement for ' . $apiEndorsement['user_cid'] . ' from Division API: ' . $response->json()['message']);
                            }
                        }
                    }
                });

            } else {
                $this->error('Failed to fetch endorsements from Division API');

                return Command::FAILURE;
            }
        }

        $this->info('Done!');

    }
}
