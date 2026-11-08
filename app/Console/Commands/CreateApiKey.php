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
    protected $signature = 'create:apikey {--integration= : Stable integration identifier, e.g. staffing-adbj}';

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
        $integrationId = $this->option('integration');
        if ($integrationId !== null && ! preg_match('/\A[a-z0-9][a-z0-9_-]{0,63}\z/', $integrationId)) {
            $this->error('Integration identifiers must use 1–64 lowercase letters, numbers, underscores or hyphens.');

            return self::FAILURE;
        }

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
            'integration_id' => $integrationId,
        ]);

        $this->comment('API key `' . $name . '` has been created with following token: `' . $secret . '`');

        return self::SUCCESS;
    }
}
