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
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();

        // Delete old Vatbook bookings
        $schedule->call(function () {
            DB::table('bookings')->where('date', '<', date('Y-m-d'))->delete();
        })->daily();

        // Update Vatbook bookings
        $schedule->command('update:bookings')
            ->everyFiveMinutes();

        // Clean up expired solo endorsements
        $schedule->command('clean:endorsements')
            ->everyMinute();

        // Close expired votes
        $schedule->command('clean:votes')
            ->everyMinute();
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
