<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class KeyGet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the current application key';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->info('Current application key, copy the whole next line:');
        $this->info(config('app.key'));

        return Command::SUCCESS;
    }
}
