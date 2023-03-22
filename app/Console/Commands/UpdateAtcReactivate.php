<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\AtcActivity;
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

        $atcHourRecords = AtcActivity::where('hours', '>=', Setting::get('atcActivityRequirement'))->get();
        $count = 0;
        foreach ($atcHourRecords as $record) {
            $handover = $record->user->handover;
            if ($handover->atc_active == false) {
                $handover->atc_active = true;
                $handover->save();
                $count++;
            }
        }

        $this->info('Successfully reactivated ' . $count . ' users!');

        return Command::SUCCESS;
    }
}
