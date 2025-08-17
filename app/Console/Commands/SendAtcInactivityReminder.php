<?php

namespace App\Console\Commands;

use anlutro\LaravelSettings\Facade as Setting;
use App\Models\AtcActivity;
use App\Notifications\AtcSoonInactiveNotification;
use Illuminate\Console\Command;

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

        $atcActivities = AtcActivity::where(function ($query) {
            // Don't send reminders if the user has been warned in the last 30 days
            $query->whereNull('last_inactivity_warning')
                ->orWhere('last_inactivity_warning', '<', now()->subDays(30));
        })
            ->where(function ($query) {
                // Only send reminders if the controller's grace period is 2/3rd completed or outside grace. This way they get headsup before it expires the day grace period ends.
                $query->where('start_of_grace_period', '<=', now()->subMonths(Setting::get('atcActivityGracePeriod', 12) * 0.66))
                    ->orWhereNull('start_of_grace_period');
            })
            ->get();

        foreach ($atcActivities as $atcActivity) {
            if ($atcActivity->atc_active == true && $atcActivity->hours < Setting::get('atcActivityInactivityReminder')) {
                $atcActivity->user->notify(new AtcSoonInactiveNotification($atcActivity->user, $atcActivity->area, $atcActivity->hours));

                $atcActivity->last_inactivity_warning = now();
                $atcActivity->save();
            }
        }

    }
}
