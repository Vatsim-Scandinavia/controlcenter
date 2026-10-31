<?php

namespace Tests\Feature\Modules;

use App\Models\Module as ModuleRecord;
use App\Modules\Exceptions\ModuleRegistrationException;
use App\Modules\ModuleRegistrar;
use App\Modules\ModuleRegistry;
use ControlCenter\ModuleApi\Navigation\NavigationItem;
use ControlCenter\ModuleApi\Ui\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\Fixtures\Modules\IncompatibleFixtureModule;
use Tests\TestCase;

class ModuleRegistryTest extends TestCase
{
    use RefreshDatabase;

    private function registry(): ModuleRegistry
    {
        config(['modules.register' => [ExampleModule::class]]);
        config(['modules.safe_mode' => false]);

        $registry = app(ModuleRegistry::class);
        $registry->flush();

        return $registry;
    }

    #[Test]
    public function it_discovers_bundled_modules_and_they_start_disabled(): void
    {
        $registry = $this->registry();

        $this->assertArrayHasKey('example', $registry->discovered());
        $this->assertFalse($registry->isEnabled('example'));
        $this->assertSame([], $registry->booted());
    }

    #[Test]
    public function enabling_a_module_boots_it_and_exposes_its_registrations(): void
    {
        $registry = $this->registry();
        $registry->enable('example');

        $this->assertTrue($registry->isEnabled('example'));
        $this->assertArrayHasKey('example', $registry->booted());
        $this->assertArrayHasKey('modules.example.view', $registry->permissions());
        $this->assertNotEmpty($registry->navigation());
        $this->assertInstanceOf(NavigationItem::class, $registry->navigation()[0]);
        $this->assertNotEmpty($registry->slot(Slot::DashboardWidgets));

        $registry->disable('example');
        $this->assertSame([], $registry->booted());
        $this->assertSame([], $registry->permissions());
    }

    #[Test]
    public function privacy_handlers_are_exposed_for_disabled_modules(): void
    {
        $registry = $this->registry();

        // Module is disabled, but its data still exists, so its handler must be found.
        $this->assertArrayHasKey('example', $registry->privacyHandlers());
    }

    #[Test]
    public function incompatible_modules_are_discovered_but_never_booted(): void
    {
        config(['app.version' => '7.2.2']);
        config(['modules.register' => [IncompatibleFixtureModule::class]]);
        $registry = app(ModuleRegistry::class);
        $registry->flush();
        $registry->enable('incompatible');

        $module = $registry->discovered()['incompatible'];
        $this->assertFalse($module->compatible);
        $this->assertFalse($module->shouldBoot());
        $this->assertArrayNotHasKey('incompatible', $registry->booted());
        $this->assertStringContainsString('requires Control Center', $module->incompatibilityReason());
    }

    #[Test]
    public function safe_mode_suppresses_all_modules_but_keeps_them_discoverable(): void
    {
        $registry = $this->registry();
        $registry->enable('example');

        config(['modules.safe_mode' => true]);
        $registry->flush();

        $this->assertArrayHasKey('example', $registry->discovered());
        $this->assertSame([], $registry->booted());
    }

    #[Test]
    public function a_missing_modules_table_does_not_crash_discovery(): void
    {
        Schema::drop('modules');

        $registry = $this->registry();

        $this->assertArrayHasKey('example', $registry->discovered());
        $this->assertFalse($registry->isEnabled('example'));
    }

    #[Test]
    public function registering_a_permission_outside_the_module_namespace_throws(): void
    {
        $registrar = new ModuleRegistrar('example');

        $this->expectException(ModuleRegistrationException::class);
        $registrar->permission('system.settings.manage', 'Hijack core');
    }

    #[Test]
    public function enable_is_idempotent_and_recorded_in_the_database(): void
    {
        $registry = $this->registry();
        $registry->enable('example');
        $registry->enable('example');

        $this->assertSame(1, ModuleRecord::query()->where('slug', 'example')->count());
    }
}
