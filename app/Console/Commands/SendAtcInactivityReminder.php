<?php

namespace App\Console\Commands;

use App\Models\AtcActivity;
use Illuminate\Console\Command;
use App\Models\User;
use App\Notifications\AtcSoonInactiveNotification;
use anlutro\LaravelSettings\Facade as Setting;

class SendAtcInactivityReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:atcinactivityreminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends a periodic ATC inactivity reminder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (Setting::get('atcActivityInactivityReminder') == 0) {
            $this->info('ATC inactivity reminder is disabled.');
            return;
        }

        $atcActivities = AtcActivity::whereNull('last_inactivity_warning')
            ->orWhere('last_inactivity_warning', '<', now()->subDays(30))
            ->get();

        foreach ($atcActivities as $atcActivity) {
            if($atcActivity->atc_active == true && $atcActivity->hours < 20){
                $atcActivity->user->notify(new AtcSoonInactiveNotification($atcActivity->user, $atcActivity->area, $atcActivity->hours));

                $atcActivity->last_inactivity_warning = now();
                $atcActivity->save();
            }
        }

    }
}
