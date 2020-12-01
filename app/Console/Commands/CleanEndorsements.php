<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanEndorsements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:endorsements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and clean expired solo endorsements';

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
        DB::table('solo_endorsements')->where('expires_at', '<', date('Y-m-d H:i:s'))->delete();
        $this->info('All expired solo endorsements have been cleaned.');
    }
}
