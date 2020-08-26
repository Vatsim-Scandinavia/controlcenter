<?php

namespace App\Console\Commands;

use App\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean and purge old logs';

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

        $entries = ActivityLog::where('created_at', '<', Carbon::now()->subWeeks(2))->get();
        foreach($entries as $entry){
            $entry->remote_addr = NULL;
            $entry->user_agent = NULL;
            $entry->save();
        }

        DB::table('activity_logs')->where('created_at', '<', Carbon::now()->subMonths(3)->format('Y-m-d H:i:s'))->delete();
        
        $this->info('All logs older than two weeks has been purged for IP and user agent details. Logs older than three months have been deleted.');
    }
}
