<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Handover;
use App\Models\Endorsement;
use anlutro\LaravelSettings\Facade as Setting;
use Illuminate\Support\Facades\Config;
use VatsimRating;

class UpdateAtcActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:atc:activity {user?*}';

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

        $this->info('Starting activity checks ...');

        $optionalUserIdFilter = $this->argument('user');
        $handoverMembers = Handover::getActiveAtcMembers($optionalUserIdFilter);
        $activeUsers = User::whereIn('id', $handoverMembers->pluck('id'))->has('atcActivity')->with('atcActivity')->get();

        $usersToSetAsInactive = $activeUsers
            ->filter(fn($m) => $this::hasTooFewHours($m)) 
            ->filter(fn($m) => $this::notInGracePeriod($m))
            ->filter(fn($m) => $this::notInS1Training($m));


        $this->info('Making '.$usersToSetAsInactive->count().' users inactive');
        Handover::whereIn('id', $usersToSetAsInactive->pluck('id'))->update(['atc_active' => false]);
        Endorsement::whereIn('user_id', $usersToSetAsInactive->pluck('id'))->where('type', 'S1')->where('valid_to', null)->update(['revoked' => true, 'valid_to' => now()]);

        return Command::SUCCESS;
    }

    /**
     * Check if the member has too few online hours to be considered active.
     * @param User member
     */
    private static function hasTooFewHours(User $member) {
        return $member->atcActivity->hours < Setting::get('atcActivityRequirement', 10);
    }

    /**
     * Check if the member is outside of their grace period.
     * @param User member
     */
    private static function notInGracePeriod(User $member) {
        $graceLengthMonths = Setting::get('atcActivityGracePeriod', 12);
        return $member->atcActivity->start_of_grace_period == null || now()->subMonths($graceLengthMonths)->gt($member->atcActivity->start_of_grace_period);
    }

    /**
     * Check if the member is outside of S1 training.
     *
     * - Isn't S1, returns true.
     * - Is S1, under permanent endorsement, returns true.
     * - Is S1, is under active training, returns false.
     * 
     * We need to exclude the active endorsement check from non-S1 ATC in order
     * to not exclude them all from the inactivity check. This is "currently"
     * redundant because the set of active ATC members does not include any
     * S1-rated members under active training, but if it does...
     *
     * @param User member
     */
    private static function notInS1Training(User $member) {
        if(VatsimRating::from($member->rating) != VatsimRating::S1){
            return true;
        }

        return $member->hasActiveEndorsement('S1', true);
    }

}
