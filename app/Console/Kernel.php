<?php

namespace App\Console;

use anlutro\LaravelSettings\Facade as Setting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CleanEndorsements::class,
        Commands\UpdateMemberDetails::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Update training queue calculations
        $schedule->command('update:queuecalculation')
            ->daily();

        // Update bookings, also deletes old bookings
        $schedule->command('update:bookings')
            ->everyFiveMinutes();

        // Monitor who's online
        $schedule->command('check:controllers')
            ->everyTenMinutes();

        // Delete old Sweatbox bookings
        $schedule->command('clean:sweatbooks')
            ->daily();

        // Clean up expired solo endorsements
        $schedule->command('clean:endorsements')
            ->everyMinute();

        // Close expired votes
        $schedule->command('clean:votes')
            ->everyMinute();

        // Clean IP addresses and user agent information from old logs and very old logs
        $schedule->command('clean:logs')
            ->daily();

        // Daily fetch updated member data from OAuth provider
        $schedule->command('update:member:data')
            ->daily();

        // Automaticaly clean memebers and trainings no longer eligble
        $schedule->command('update:member:details')
            ->dailyAt('05:00');

        // Update ATC hours and active status
        $schedule->command('update:atc:status')
            ->dailyAt('06:00');

        // Send our training interest e-mails
        $schedule->command('send:traininginterest')
            ->dailyAt('12:00');

        // Expire workmail addresses
        $schedule->command('update:workmails')
            ->daily();

        // Send task notifications
        $schedule->command('send:task:notifications')
            ->hourly();

        // Send telemetry data
        if (Setting::get('telemetryEnabled')) {
            $schedule->command('send:telemetry')
                ->daily();
        }

        // Check if updates are available
        $schedule->command('check:update')
            ->hourly();

        // Log last cronjob time
        $schedule->call(function () {
            Setting::set('_lastCronRun', now());
            Setting::save();
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
