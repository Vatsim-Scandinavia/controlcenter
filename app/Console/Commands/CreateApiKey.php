<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use App\Modules\ModuleRegistry;
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
    public function handle(ModuleRegistry $registry)
    {
        // Gather details from input
        $choices = [
            'NO, read only',
            'YES, allow editing data',
        ];
        $choice = $this->choice('Should the API key have edit rights?', $choices);
        $readonly = $choice == $choices[0];

        $name = $this->ask('What should we name the API Key?');

        $scopes = $this->chooseScopes($registry);

        // Generate key
        $secret = Uuid::uuid4();
        ApiKey::create([
            'id' => $secret,
            'name' => $name,
            'read_only' => $readonly,
            'scopes' => $scopes ?: null,
            'created_at' => now(),
        ]);

        $this->comment('API key `' . $name . '` has been created with following token: `' . $secret . '`');
    }

    /**
     * Let the operator grant any module-registered API scopes. Returns the
     * selected scope names, or an empty array when none are chosen or available.
     *
     * @return array<int, string>
     */
    private function chooseScopes(ModuleRegistry $registry): array
    {
        $available = array_keys($registry->apiScopes());

        if ($available === []) {
            return [];
        }

        $none = 'None';
        $selected = (array) $this->choice(
            'Which module API scopes should this key have? (comma-separate for multiple)',
            [$none, ...$available],
            $none,
            null,
            true,
        );

        return array_values(array_filter($selected, fn (string $scope): bool => $scope !== $none));
    }
}
