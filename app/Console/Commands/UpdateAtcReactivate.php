<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\AtcActivity;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateAtcReactivate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:atc:reactivate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set users that meet ATC hours to active state again';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (! Setting::get('atcActivityAllowReactivation')) {
            $this->error('This command is only available when reactivation setting is enabled');

            return Command::FAILURE;
        }

        $count = 0;

        if (Setting::get('atcActivityBasedOnTotalHours', true)) {

            $inactiveUsers = User::whereHas('atcActivity', function ($query) {
                $query->where('atc_active', false);
            })->get();

            foreach ($inactiveUsers as $user) {
                $totalHours = $user->atcActivity->sum('hours');
                if ($totalHours >= Setting::get('atcActivityRequirement')) {
                    AtcActivity::where('user_id', $user->id)->update(['atc_active' => 1]);
                    $count++;
                }
            }

        } else {

            $atcHourRecords = AtcActivity::where('hours', '>=', Setting::get('atcActivityRequirement'))->get();
            foreach ($atcHourRecords as $record) {
                if ($record->atc_active == false) {
                    $record->atc_active = true;
                    $record->save();
                    $count++;
                }
            }

        }

        $this->info('Successfully reactivated ' . $count . ' users/records!');

        return Command::SUCCESS;
    }
}
