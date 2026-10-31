<?php

namespace Tests\Feature\Modules;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

class ModuleApiScopeTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    private function key(string $id, array $scopes = []): ApiKey
    {
        return ApiKey::create([
            'id' => $id,
            'name' => $id,
            'read_only' => true,
            'scopes' => $scopes ?: null,
            'created_at' => now(),
        ]);
    }

    #[Test]
    public function a_key_with_the_module_scope_reaches_the_scoped_route(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);
        $this->key('scoped', ['modules.example.read']);

        $this->withToken('scoped')
            ->getJson('/api/modules/example/ping')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    #[Test]
    public function a_key_without_the_module_scope_is_rejected(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);
        $this->key('unscoped'); // legacy key, no scopes

        $this->withToken('unscoped')
            ->getJson('/api/modules/example/ping')
            ->assertUnauthorized();
    }

    #[Test]
    public function a_missing_token_is_rejected_on_a_scoped_route(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $this->getJson('/api/modules/example/ping')->assertUnauthorized();
    }

    #[Test]
    public function a_scoped_route_is_not_served_when_the_module_is_disabled(): void
    {
        $this->bootModules([ExampleModule::class]); // discovered but disabled
        $this->key('scoped', ['modules.example.read']);

        $this->withToken('scoped')
            ->getJson('/api/modules/example/ping')
            ->assertNotFound();
    }
}
