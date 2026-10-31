<?php

namespace Tests\Feature\Modules;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

class CreateApiKeyScopeTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    #[Test]
    public function it_assigns_a_module_scope_to_a_new_key(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $this->artisan('create:apikey')
            ->expectsChoice('Should the API key have edit rights?', 'NO, read only', ['NO, read only', 'YES, allow editing data'])
            ->expectsQuestion('What should we name the API Key?', 'scoped-key')
            ->expectsChoice(
                'Which module API scopes should this key have? (comma-separate for multiple)',
                ['modules.example.read'],
                ['None', 'modules.example.read'],
            )
            ->assertSuccessful();

        $key = ApiKey::query()->where('name', 'scoped-key')->first();

        $this->assertNotNull($key);
        $this->assertSame(['modules.example.read'], $key->scopes);
    }

    #[Test]
    public function it_creates_an_unscoped_key_when_no_module_scopes_exist(): void
    {
        $this->artisan('create:apikey')
            ->expectsChoice('Should the API key have edit rights?', 'NO, read only', ['NO, read only', 'YES, allow editing data'])
            ->expectsQuestion('What should we name the API Key?', 'plain-key')
            ->assertSuccessful();

        $key = ApiKey::query()->where('name', 'plain-key')->first();

        $this->assertNotNull($key);
        $this->assertNull($key->scopes);
    }
}
