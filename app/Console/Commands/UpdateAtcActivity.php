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

        if ($optionalUserIdFilter) {
            $activeAreas = AtcActivity::where('atc_active', true)->whereIn('user_id', $optionalUserIdFilter)->get();
        } else {
            $activeAreas = AtcActivity::where('atc_active', true)->get();
        }

        $atcActiveAreasToSetAsInactive = $activeAreas
            ->filter(fn ($a) => $this::hasTooFewHours($a))
            ->filter(fn ($a) => $this::notInGracePeriod($a));

        if ($dryRun) {
            $this->info('[DRY RUN] We would have made ' . $atcActiveAreasToSetAsInactive->count() . ' areas inactive');
            $this->info('[DRY RUN] Specifically: ' . $atcActiveAreasToSetAsInactive->pluck('id'));

            return Command::SUCCESS;
        }

        // Execute updates on relevant areas
        $this->info('Making ' . $atcActiveAreasToSetAsInactive->count() . ' areas inactive');
        AtcActivity::whereIn('id', $atcActiveAreasToSetAsInactive->pluck('id'))->update(['atc_active' => false]);

        // Only once if all areas are counted as one, per area if counted per area
        if (Setting::get('atcActivityBasedOnTotalHours', true)) {

            $sentToUsers = collect();
            foreach ($atcActiveAreasToSetAsInactive as $atcActivity) {

                // Skip if already sent to this user
                if ($sentToUsers->contains($atcActivity->user->id)) {
                    continue;
                }

                // Send one notification to the user going inactive
                $atcActivity->user->notify(new InactivityNotification($atcActivity->user));
                $sentToUsers->push($atcActivity->user->id);
            }
        } else {
            foreach ($atcActiveAreasToSetAsInactive as $atcActivity) {
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
     * Check if the member is outside of their grace period.
     *
     * @param AtcActivity atcActivity
     */
    private static function notInGracePeriod(AtcActivity $atcActivity)
    {
        // Grace period is not set or has expired
        return $atcActivity->start_of_grace_period == null || now()->subMonths(Setting::get('atcActivityGracePeriod', 12))->gte($atcActivity->start_of_grace_period);
    }
}
