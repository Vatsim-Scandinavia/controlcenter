<?php

namespace App\Console\Commands;

use App\Models\Vote;
use Illuminate\Console\Command;

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
        // Iterate models rather than a bulk query-builder update so the
        // authoritative Eloquent `updated` event fires and the VoteClosed
        // domain event reaches module listeners.
        Vote::where('end_at', '<=', now())
            ->where('closed', false)
            ->get()
            ->each(function (Vote $vote): void {
                $vote->closed = true;
                $vote->save();
            });

        $this->info('All expired votes have been closed.');
    }
}
