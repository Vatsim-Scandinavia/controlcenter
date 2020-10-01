<?php

namespace App\Console;


use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

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
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        // Update training queue calculations
        $schedule->command('update:queuecalculation')
            ->daily();

        // Update Vatbook bookings, also deletes old bookings
        $schedule->command('update:bookings')
            ->everyFiveMinutes();

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

        $schedule->command('update:members')
                 ->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
