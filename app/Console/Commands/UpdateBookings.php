<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

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
    protected $description = 'Update bookings';

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
     * @return void
     */
    public function handle()
    {
        // Remove expired bookings
        DB::table('bookings')->where('time_end', '<', date('Y-m-d H:i:s'))->delete();

        $this->info('All bookings have been updated.');
    }
}
