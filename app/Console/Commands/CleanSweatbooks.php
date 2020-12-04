<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanSweatbooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:sweatbooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired sweatbooks';

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
        DB::table('sweatbooks')->where('date', '<', date('Y-m-d'))->delete();
        $this->info('All old sweatbooks have been cleaned.');
    }
}
