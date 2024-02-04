<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\VatsimRating;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\User;
use App\Notifications\InactivityNotification;
use Illuminate\Console\Command;

class UpdateAtcActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:atc:activity {user?*} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update activity status for users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Arguments and options
        $this->info('Starting activity checks ...');
        $optionalUserIdFilter = $this->argument('user');

        $dryRun = false;
        if ($this->option('dry-run') != null) {
            $dryRun = true;
        }

        $activeMembers = User::getActiveAtcMembers($optionalUserIdFilter);
        $activeUsers = User::whereIn('id', $activeMembers->pluck('id'))->has('atcActivity')->with('atcActivity')->get();

        // Filter users
        $usersToSetAsInactive = $activeUsers
            ->filter(fn ($m) => $this::hasTooFewHours($m))
            ->filter(fn ($m) => $this::notInGracePeriod($m));

        if ($dryRun) {
            $this->info('[DRY RUN] We would have made ' . $usersToSetAsInactive->count() . ' users inactive');
            $this->info('[DRY RUN] Specifically: ' . $usersToSetAsInactive->pluck('id'));

            return Command::SUCCESS;
        }

        // Execute updates on relevant users
        $this->info('Making ' . $usersToSetAsInactive->count() . ' users inactive');
        User::whereIn('id', $usersToSetAsInactive->pluck('id'))->update(['atc_active' => false]);

        // Send inactivity notification to the users
        if (! Setting::get('atcActivityAllowReactivation')) {
            foreach ($usersToSetAsInactive as $userToSetAsInactive) {
                $userToSetAsInactive->notify(new InactivityNotification($userToSetAsInactive));
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Check if the member has too few online hours to be considered active.
     *
     * @param User member
     */
    private static function hasTooFewHours(User $member)
    {

        if (Setting::get('atcActivityAllowTotalHours', true)) {
            return $member->atcActivity->sum('hours') < Setting::get('atcActivityRequirement', 10);
        } else {
            $allAreasInactive = true;

            foreach (Area::all() as $area) {
                $activity = $member->atcActivity->firstWhere('area_id', $area->id);
                if ($activity && $activity->hours >= Setting::get('atcActivityRequirement', 10)) {
                    $allAreasInactive = false;
                }
            }

            return $allAreasInactive;
        }

    }

    /**
     * Check if the member is outside of their grace period.
     *
     * @param User member
     */
    private static function notInGracePeriod(User $member)
    {
        $graceLengthMonths = Setting::get('atcActivityGracePeriod', 12);
        $notInGracePeriod = true;

        // If no grace is set or within grace period per area, return true
        foreach (Area::all() as $area) {

            $activity = $member->atcActivity->firstWhere('area_id', $area->id);

            if (
                $activity
                && $activity->start_of_grace_period != null
                && now()->subMonths($graceLengthMonths)->lte($activity->start_of_grace_period)
            ) {
                $notInGracePeriod = false;
            }
        }

        return $notInGracePeriod;
    }
}
