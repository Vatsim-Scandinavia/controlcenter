<?php

namespace Tests\Feature\Modules;

use App\Contracts\DivisionApiContract;
use App\Modules\Exceptions\ModuleRegistrationException;
use App\Modules\ModuleRegistrar;
use App\Modules\ModuleRegistry;
use App\Modules\ModuleScheduler;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\CoreLookupModule;
use Tests\Fixtures\Modules\StripsAuthModule;
use Tests\Fixtures\Modules\UnregisteredScheduleModule;
use Tests\Fixtures\Modules\UntokenedApiModule;
use Tests\TestCase;

/**
 * Regression tests for the fourth review round: the narrowed Registrar surface
 * (A2 route lookups, C driver allowlist/collision, scheduling, enforced route
 * middleware) and the removed methods. Console route registration (A1) needs a
 * real fresh process and lives in {@see ModuleFreshBootTest}.
 */
class ReviewRound4Test extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    // -- A2: existing routes stay resolvable inside a module callback ---------

    #[Test]
    public function a_module_callback_can_resolve_existing_route_names(): void
    {
        // CoreLookupModule's web callback calls route('login'); if staging hid
        // existing routes it would throw and the module would be rejected.
        $this->bootModules([CoreLookupModule::class], ['corelookup']);

        $this->assertTrue(Route::has('modules.corelookup.go'), 'route(\'login\') was not resolvable inside the module callback.');
    }

    // -- C: driver() allowlist and collisions --------------------------------

    #[Test]
    public function a_driver_for_a_disallowed_contract_is_rejected(): void
    {
        $registrar = new ModuleRegistrar('x');

        $this->expectException(ModuleRegistrationException::class);
        $registrar->driver(\DateTimeInterface::class, 'x', fn () => new \stdClass);
    }

    #[Test]
    public function a_driver_colliding_with_a_builtin_name_is_rejected(): void
    {
        $registrar = new ModuleRegistrar('x');

        // 'VATEUD' is a built-in division driver (config/vatsim.php).
        $this->expectException(ModuleRegistrationException::class);
        $registrar->driver(DivisionApiContract::class, 'VATEUD', fn () => new \stdClass);
    }

    #[Test]
    public function an_allowed_driver_with_a_free_name_is_accepted(): void
    {
        $registrar = new ModuleRegistrar('x');
        $registrar->driver(DivisionApiContract::class, 'x-custom', fn () => new \stdClass);

        $this->assertCount(1, $registrar->drivers);
    }

    // -- C: schedule() may only schedule the module's own command -------------

    #[Test]
    public function scheduling_an_unregistered_command_is_refused(): void
    {
        $this->bootModules([UnregisteredScheduleModule::class], ['unregsched']);

        $schedule = new Schedule;
        ModuleScheduler::schedule($schedule, app(ModuleRegistry::class));

        $commands = array_map(fn ($event) => (string) $event->command, $schedule->events());
        $this->assertFalse(
            collect($commands)->contains(fn ($c) => str_contains($c, 'modules:example')),
            'A command not registered by the module must not be scheduled.',
        );
    }

    // -- C: enforced route middleware ----------------------------------------

    #[Test]
    public function a_module_api_route_requires_a_key_by_default(): void
    {
        // UntokenedApiModule's api route declares no api-token itself; the group
        // must still require a key.
        $this->bootModules([UntokenedApiModule::class], ['untokened']);

        $this->getJson('/api/modules/untokened/open')->assertStatus(401);
    }

    #[Test]
    public function a_module_stripping_a_mandatory_middleware_is_rejected(): void
    {
        // StripsAuthModule strips 'auth' from its route; the whole module's
        // routes must be discarded.
        $this->bootModules([StripsAuthModule::class], ['stripsauth']);

        $this->assertFalse(Route::has('modules.stripsauth.open'), 'A route stripping mandatory middleware was registered.');
    }

    // -- C: removed Registrar methods -----------------------------------------

    #[Test]
    public function the_registrar_no_longer_exposes_bind_or_privacy_handler(): void
    {
        $this->assertFalse(method_exists(ModuleRegistrar::class, 'bind'), 'Registrar::bind() should be removed.');
        $this->assertFalse(method_exists(ModuleRegistrar::class, 'privacyHandler'), 'Registrar::privacyHandler() should be removed.');
    }

    // -- A3: a controlcenter-module package with no module class blocks --------

    #[Test]
    public function an_installed_package_without_a_module_class_blocks_cleanup(): void
    {
        $path = base_path('vendor/composer/installed.json');

        if (! is_file($path)) {
            $this->markTestSkipped('installed.json not present.');
        }

        $backup = (string) file_get_contents($path);

        try {
            $data = json_decode($backup, true, flags: JSON_THROW_ON_ERROR);
            $data['packages'][] = [
                'name' => 'review/invalid-module',
                'type' => 'controlcenter-module',
                'extra' => ['controlcenter' => []], // no 'module' key
            ];
            file_put_contents($path, json_encode($data));

            $registry = app(ModuleRegistry::class);
            $registry->flush();

            $this->assertArrayHasKey('package:review/invalid-module', $registry->discoveryFailures());
            $this->assertArrayHasKey('package:review/invalid-module', $registry->privacyCleanupPlan()['failed']);
        } finally {
            file_put_contents($path, $backup);
            app(ModuleRegistry::class)->flush();
        }
    }
}
