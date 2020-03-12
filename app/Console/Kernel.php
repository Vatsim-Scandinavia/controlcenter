<?php

namespace App\Console;

use App\Position;
use App\Vatbook;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Auth\User;
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
        //
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
        $schedule->call(function () {
            DB::table('bookings')->where('date', '<', date('Y-m-d'))->delete();
        })->daily();

        $schedule->call(function () {

            $feed = file_get_contents("http://vatbook.euroutepro.com/xml2.php");
            $raw = simplexml_load_string($feed)->atcs;

            foreach($raw->children() as $booking){
                if(!Position::where('callsign', $booking->callsign)->get()->isEmpty()) {
                    User::Where('id', $booking->cid)->get()->isEmpty() ? $uid = null : $uid = User::where('id', $booking->cid)->first()->id;
                    Vatbook::updateOrCreate(['eu_id' => $booking->id], ['callsign' => $booking->callsign, 'position_id' => Position::all()->firstWhere('callsign', $booking->callsign)->id, 'name' => $booking->name, 'time_start' => $booking->time_start, 'time_end' => $booking->time_end, 'cid' => $booking->cid, 'user_id' => $uid, 'training' => false, 'event' => false]);
                }
            }

            foreach(Vatbook::whereNull('local_id')->get() as $booking) {
                $count = 0;
                foreach($raw->children() as $element){
                    if($element->id == $booking->eu_id) $count =+ 1;
                }
                if($count !== 1) $booking->delete();
            }

        })->everyFiveMinutes();
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
