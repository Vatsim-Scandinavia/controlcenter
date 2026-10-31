<?php

namespace Tests\Feature\Modules;

use App\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

class ModuleCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['modules.register' => [ExampleModule::class]]);
        config(['modules.safe_mode' => false]);
        app(ModuleRegistry::class)->flush();
    }

    #[Test]
    public function module_list_shows_installed_modules(): void
    {
        $this->artisan('module:list')
            ->expectsOutputToContain('example')
            ->assertSuccessful();
    }

    #[Test]
    public function module_enable_and_disable_toggle_state(): void
    {
        $this->artisan('module:enable', ['slug' => 'example'])
            ->expectsOutputToContain('enabled')
            ->assertSuccessful();

        $this->assertTrue(app(ModuleRegistry::class)->isEnabled('example'));

        $this->artisan('module:disable', ['slug' => 'example'])
            ->expectsOutputToContain('disabled')
            ->assertSuccessful();

        $this->assertFalse(app(ModuleRegistry::class)->isEnabled('example'));
    }

    #[Test]
    public function module_enable_rejects_an_unknown_slug(): void
    {
        $this->artisan('module:enable', ['slug' => 'does-not-exist'])
            ->assertFailed();
    }

    #[Test]
    public function module_info_prints_the_manifest(): void
    {
        $this->artisan('module:info', ['slug' => 'example'])
            ->expectsOutputToContain('Example Module')
            ->assertSuccessful();
    }
}
