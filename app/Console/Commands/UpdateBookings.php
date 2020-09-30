<?php

namespace App\Console\Commands;

use DB;
use App\Position;
use App\Vatbook;
use Illuminate\Console\Command;
use Illuminate\Foundation\Auth\User;

class UpdateBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:bookings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Vatbook bookings';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
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

        // Remove expired bookings
        DB::table('vatbooks')->where('time_end', '<', date('Y-m-d H:i:s'))->delete();

        $this->info('All Vatbook bookings have been updated.');
    }
}