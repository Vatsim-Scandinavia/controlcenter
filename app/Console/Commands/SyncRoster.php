<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Helpers\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Services\DivisionApi\DivisionApiError;
use App\Tasks\Types\RatingUpgrade;
use Illuminate\Console\Command;

class SyncRoster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:roster';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync the roster with Division API';

    /**
     * How long a requested rating upgrade shields a member from roster removal.
     *
     * User.rating is only refreshed by update:member:data (daily 04:00) from the OAuth
     * provider, so a member whose upgrade Core has already granted can read as OBS here
     * for up to a day. This window comfortably exceeds that propagation while keeping the
     * accepted edge case short: a member upgraded and then suspended inside the window
     * stays on Core until it lapses.
     */
    private const UPGRADE_GRACE_DAYS = 30;

    /**
     * Execute the console command.
     */
    public function handle()
    {

        if (! Setting::get('divisionApiEnabled')) {
            $this->error('This command is only available when Division API setting is enabled');

            return Command::FAILURE;
        }

        $this->info('Syncing roster with Division API...');

        $rosterResponse = DivisionApi::getRoster();
        if ($rosterResponse && $rosterResponse->successful()) {
            $json = $rosterResponse->json();
            if (isset($json['data']) && isset($json['data']['roster_members'])) {
                $rosteredMembers = collect($json['data']['roster_members'])->pluck('user_cid');
                $activeMembers = User::getActiveAtcMembers()->pluck('id');
                $visitingMembers = User::whereHas('endorsements', function ($query) {
                    $query->where('type', 'VISITING')->where('revoked', false)->where('expired', false);
                })->get()->pluck('id');
                $activeMembers = $activeMembers->merge($visitingMembers)->unique();

                // Add members who don't exist in roster
                $this->info('Adding new members to roster...');
                $newMembers = $activeMembers->diff($rosteredMembers);
                $newMembers->each(function ($memberId) {
                    $response = DivisionApi::assignRosterUser($memberId);
                    if ($response->successful()) {
                        $this->info('Added member ' . $memberId . ' to roster.');
                    } else {
                        $this->error('Failed to add member ' . $memberId . ' to roster: ' . DivisionApiError::detail($response));
                    }
                });

                // Remove member who are not active anymore
                $this->info('Removing members from roster...');
                // Members whose rating upgrade was recently requested are still waiting for
                // VATSIM to grant it, so their local rating lags behind the roster. A completed
                // RatingUpgrade task is that request: requestRatingUpgrade() runs only inside
                // RatingUpgrade::complete(). Removing them would undo the upgrade we just asked for.
                $upgradeInProgress = Task::query()
                    ->where('type', RatingUpgrade::class)
                    ->where('status', TaskStatus::COMPLETED)
                    ->where('closed_at', '>=', now()->subDays(self::UPGRADE_GRACE_DAYS))
                    ->pluck('subject_user_id')
                    ->unique();

                $removedMembers = $rosteredMembers->diff($activeMembers)->diff($upgradeInProgress);
                $removedMembers->each(function ($memberId) {
                    $response = DivisionApi::removeRosterUser($memberId);
                    if ($response->successful()) {
                        $this->info('Removed member ' . $memberId . ' from roster.');
                    } else {
                        $this->error('Failed to remove member ' . $memberId . ' from roster: ' . DivisionApiError::detail($response));
                    }
                });

                $this->info('Syncing roster with Division API completed.');
            }
        } else {
            $this->error('Failed to sync roster with Division API: ' . DivisionApiError::detail($rosterResponse));
        }

    }
}
