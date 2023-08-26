<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;
use Ramsey\Uuid\Uuid;

class CreateApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:apikey';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates an API key';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Gather details from input
        $choices = [
            'NO, read only',
            'YES, allow editing data',
        ];
        $choice = $this->choice('Should the API key have edit rights?', $choices);
        $readonly = $choice == $choices[0];

        $name = $this->ask('What should we name the API Key?');

        // Generate key
        $secret = Uuid::uuid4();
        ApiKey::create([
            'id' => $secret,
            'name' => $name,
            'read_only' => $readonly,
            'created_at' => now(),
        ]);

        $this->comment('API key `' . $name . '` has been created with following token: `' . $secret . '`');
    }
}
