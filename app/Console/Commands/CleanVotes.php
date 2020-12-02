<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanVotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:votes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and close votes due to be locked';

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
        DB::table('votes')->where('end_at', '<=', date('Y-m-d H:i:s'))->update(['closed' => 1]);
        $this->info('All expired votes have been closed.');
    }
}
