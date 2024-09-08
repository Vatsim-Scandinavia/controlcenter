<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Models\User;
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
                        $this->error('Failed to add member ' . $memberId . ' to roster: ' . $response->json()['message']);
                    }
                });

                // Remove member who are not active anymore
                $this->info('Removing members from roster...');
                $removedMembers = $rosteredMembers->diff($activeMembers);
                $removedMembers->each(function ($memberId) {
                    $response = DivisionApi::removeRosterUser($memberId);
                    if ($response->successful()) {
                        $this->info('Removed member ' . $memberId . ' from roster.');
                    } else {
                        $this->error('Failed to remove member ' . $memberId . ' from roster: ' . $response->json()['message']);
                    }
                });

                $this->info('Syncing roster with Division API completed.');
            }
        } else {
            $this->error('Failed to sync roster with Division API: ' . $rosterResponse->json()['message']);
        }

    }
}
