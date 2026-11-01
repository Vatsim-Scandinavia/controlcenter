<?php

namespace Tests\Feature\Modules;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

class ModuleRouteTest extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    #[Test]
    public function an_enabled_module_serves_its_web_routes_under_the_module_prefix(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        $this->assertTrue(Route::has('modules.example.index'));
        $this->assertSame('modules/example', ltrim(route('modules.example.index', [], false), '/'));

        $this->actingAs(User::factory()->create())
            ->get('/modules/example')
            ->assertOk()
            ->assertSee('example module index');
    }

    #[Test]
    public function a_disabled_module_route_returns_not_found(): void
    {
        $this->bootModules([ExampleModule::class]); // discovered but not enabled

        $this->actingAs(User::factory()->create())
            ->get('/modules/example')
            ->assertNotFound();
    }
}
