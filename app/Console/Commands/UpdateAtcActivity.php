<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\Area;
use App\Models\AtcActivity;
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

        // Get users deemed as active
        $activeUsers = User::getActiveAtcMembers($optionalUserIdFilter);
        $atcActivitiesToSetAsInactive = collect();

        foreach ($activeUsers as $user) {

            if (Setting::get('atcActivityBasedOnTotalHours', true)) {

                $userActivities = $user->atcActivity;
                $totalHours = $userActivities->sum('hours');
                $hasAnyGrace = $userActivities
                    ->where('start_of_grace_period', '>=', now()->subMonths(Setting::get('atcActivityGracePeriod', 12)))
                    ->count();

                if ($totalHours < Setting::get('atcActivityRequirement', 10) && ! $hasAnyGrace) {
                    $userActivities->map(fn ($a) => $atcActivitiesToSetAsInactive->push($a));
                }

            } else {
                $user->atcActivity->where('atc_active', true)
                    ->filter(fn ($a) => $this::hasTooFewHours($a))
                    ->filter(fn ($a) => $this::notInGracePeriod($a))
                    ->map(fn ($a) => $atcActivitiesToSetAsInactive->push($a));
            }
        }

        if ($dryRun) {
            $this->info('[DRY RUN] We would have made ' . $atcActivitiesToSetAsInactive->count() . ' areas inactive');
            $this->info('[DRY RUN] Specifically: ' . $atcActivitiesToSetAsInactive->pluck('id'));

            return Command::SUCCESS;
        }

        // Execute updates on relevant areas
        $this->info('Making ' . $atcActivitiesToSetAsInactive->count() . ' areas inactive');
        AtcActivity::whereIn('id', $atcActivitiesToSetAsInactive->pluck('id'))->update(['atc_active' => false]);

        // Only once if all areas are counted as one, per area if counted per area
        if (Setting::get('atcActivityBasedOnTotalHours', true)) {

            $sentToUsers = collect();
            foreach ($atcActivitiesToSetAsInactive as $atcActivity) {

                // Skip if already sent to this user
                if ($sentToUsers->contains($atcActivity->user->id)) {
                    continue;
                }

                // Send one notification to the user going inactive
                $atcActivity->user->notify(new InactivityNotification($atcActivity->user));
                $sentToUsers->push($atcActivity->user->id);
            }
        } else {
            foreach ($atcActivitiesToSetAsInactive as $atcActivity) {
                // Send notification(s) to all users who went inactive per area
                $atcActivity->user->notify(new InactivityNotification($atcActivity->user, $atcActivity->area));
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Check if the member has too few online hours to be considered active.
     *
     * @param AtcActivity atcActivity
     */
    private static function hasTooFewHours(AtcActivity $atcActivity)
    {
        return $atcActivity->hours < Setting::get('atcActivityRequirement', 10);
    }

    /**
     * Check if the member is outside of their grace period. Grace period is not set or has expired
     *
     * @param AtcActivity atcActivity
     */
    private static function notInGracePeriod(AtcActivity $atcActivity)
    {
        return $atcActivity->start_of_grace_period == null || now()->subMonths(Setting::get('atcActivityGracePeriod', 12))->gte($atcActivity->start_of_grace_period);
    }
}
