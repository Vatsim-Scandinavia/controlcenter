<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateApiKeyTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('validIntegrationIdentifiers')]
    public function test_command_persists_valid_integration_identifiers(string $identifier, bool $readOnly): void
    {
        $this->artisan('create:apikey', ['--integration' => $identifier])
            ->expectsChoice('Should the API key have edit rights?', $readOnly ? 'NO, read only' : 'YES, allow editing data', ['NO, read only', 'YES, allow editing data'])
            ->expectsQuestion('What should we name the API Key?', 'Test integration')
            ->assertSuccessful();

        $key = ApiKey::where('name', 'Test integration')->sole();
        $this->assertSame($identifier, $key->integration_id);
        $this->assertSame($readOnly, (bool) $key->read_only);
    }

    public static function validIntegrationIdentifiers(): array
    {
        return [['a', false], ['0', false], ['staffing-adbj_1', false], [str_repeat('a', 64), false], ['read-only', true]];
    }

    #[DataProvider('invalidIntegrationIdentifiers')]
    public function test_command_rejects_invalid_identifiers_without_creating_credentials(string $identifier): void
    {
        $this->artisan('create:apikey', ['--integration' => $identifier])
            ->expectsOutput('Integration identifiers must use 1–64 lowercase letters, numbers, underscores or hyphens.')
            ->assertFailed();

        $this->assertDatabaseCount('api_keys', 0);
    }

    public static function invalidIntegrationIdentifiers(): array
    {
        return [[''], ['UPPERCASE'], ['-leading'], ['_leading'], ['with space'], ['with/slash'], [str_repeat('a', 65)]];
    }

    public function test_command_still_creates_legacy_credentials_without_an_integration(): void
    {
        $this->artisan('create:apikey')
            ->expectsChoice('Should the API key have edit rights?', 'NO, read only', ['NO, read only', 'YES, allow editing data'])
            ->expectsQuestion('What should we name the API Key?', 'Legacy client')
            ->assertSuccessful();

        $key = ApiKey::where('name', 'Legacy client')->sole();
        $this->assertNull($key->integration_id);
        $this->assertTrue((bool) $key->read_only);
    }
}
