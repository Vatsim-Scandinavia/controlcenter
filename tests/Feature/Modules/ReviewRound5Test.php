<?php

namespace Tests\Feature\Modules;

use anlutro\LaravelSettings\Facade;
use App\Console\Commands\UserDelete;
use App\Contracts\DivisionApiContract;
use App\Http\Middleware\ApiToken;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureModuleEnabled;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ApiKey;
use App\Models\Module as ModuleRecord;
use App\Modules\Exceptions\ModuleRegistrationException;
use App\Modules\ModuleRegistrar;
use App\Modules\ModuleRegistry;
use App\Modules\ModuleScheduler;
use App\Services\DivisionApi\Adapters\NoOpAdapter;
use App\Tasks\TaskTypeRegistry;
use App\Tasks\Types\Custom;
use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\InteractsWithModules;
use Tests\Fixtures\Modules\DataHandlingNoHandlerModule;
use Tests\Fixtures\Modules\DriverModuleA;
use Tests\Fixtures\Modules\DriverModuleB;
use Tests\Fixtures\Modules\DynamicRouteModule;
use Tests\Fixtures\Modules\ExampleCommand;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\TestCase;

class ReviewRound5Test extends TestCase
{
    use InteractsWithModules;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DynamicRouteModule::$register = null;
    }

    private function bootWeb(Closure $routes): void
    {
        DynamicRouteModule::$register = fn ($r) => $r->web($routes);
        $this->bootModules([DynamicRouteModule::class], ['dyn']);
    }

    private function bootApi(Closure $routes): void
    {
        DynamicRouteModule::$register = fn ($r) => $r->api($routes);
        $this->bootModules([DynamicRouteModule::class], ['dyn']);
    }

    // ---- P1-1: safe mode can still disable the offending module ------------

    #[Test]
    public function a_module_can_be_disabled_under_safe_mode(): void
    {
        config(['modules.register' => [ExampleModule::class], 'modules.safe_mode' => true]);
        $registry = app(ModuleRegistry::class);
        $registry->flush();
        ModuleRecord::create(['slug' => 'example', 'enabled' => true, 'enabled_at' => now()]);
        $registry->flush();

        $module = $registry->discovered()['example'];
        $this->assertFalse($module->enabled, 'safe mode makes the effective state off');
        $this->assertTrue($module->storedEnabled, 'the database row is still on');

        $this->artisan('module:disable example')->expectsOutputToContain('disabled')->assertExitCode(0);
        $this->assertDatabaseHas('modules', ['slug' => 'example', 'enabled' => false]);
    }

    // ---- P1-2: route guarantees are enforced by resolved middleware --------

    #[Test]
    public function stripping_auth_by_class_rejects_the_module(): void
    {
        $this->bootWeb(fn () => Route::get('/x', fn () => 'OPEN')->withoutMiddleware(Authenticate::class)->name('x'));
        $this->assertFalse(Route::has('modules.dyn.x'));
        $this->get('/modules/dyn/x')->assertNotFound();
    }

    #[Test]
    public function stripping_auth_by_parent_class_does_not_expose_the_route(): void
    {
        // Excluding the framework parent does not remove the app subclass, so
        // the route stays protected (not a 200 for a guest).
        $this->bootWeb(fn () => Route::get('/x', fn () => 'OPEN')->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class)->name('x'));
        $this->assertNotSame(200, $this->get('/modules/dyn/x')->status());
    }

    #[Test]
    public function stripping_the_enabled_check_rejects_the_module(): void
    {
        $this->bootWeb(fn () => Route::get('/x', fn () => 'OPEN')->withoutMiddleware(EnsureModuleEnabled::class . ':dyn')->name('x'));
        $this->assertFalse(Route::has('modules.dyn.x'));
    }

    #[Test]
    public function stripping_csrf_rejects_the_module(): void
    {
        $this->bootWeb(fn () => Route::post('/x', fn () => 'OPEN')->withoutMiddleware(VerifyCsrfToken::class)->name('x'));
        $this->assertFalse(Route::has('modules.dyn.x'));
    }

    #[Test]
    public function set_action_clearing_middleware_rejects_the_module(): void
    {
        $this->bootWeb(function () {
            /** @var RouteObject $route */
            $route = Route::get('/x', fn () => 'OPEN')->name('x');
            $route->setAction(array_merge($route->getAction(), ['middleware' => []]));
        });
        $this->assertFalse(Route::has('modules.dyn.x'));
        $this->get('/modules/dyn/x')->assertNotFound();
    }

    #[Test]
    public function set_uri_cannot_hijack_a_core_route(): void
    {
        $this->bootWeb(fn () => Route::get('/x', fn () => 'HIJACKED')->name('x')->setUri('login'));
        $this->assertFalse(Route::has('modules.dyn.x'));
        $this->assertSame('/login', route('login', [], false));
        $this->assertStringNotContainsString('HIJACKED', (string) $this->get('/login')->getContent());
    }

    #[Test]
    public function renaming_over_a_core_route_is_rejected(): void
    {
        $this->bootWeb(function () {
            $route = Route::get('/x', fn () => 'OPEN');
            $route->action['as'] = 'login';
        });
        // The core login route still resolves to the core URI, not the module's.
        $this->assertSame('/login', route('login', [], false));
    }

    #[Test]
    public function a_raw_route_added_to_the_collection_is_rejected(): void
    {
        $this->bootWeb(function () {
            Route::getRoutes()->add((new RouteObject(['GET'], 'raw-open', fn () => 'OPEN'))->setRouter(app('router'))->setContainer(app()));
        });
        $this->get('/raw-open')->assertNotFound();
    }

    #[Test]
    public function an_api_route_stripping_api_token_by_class_is_rejected(): void
    {
        $this->bootApi(fn () => Route::get('/x', fn () => 'OPEN')->withoutMiddleware(ApiToken::class)->name('x'));
        $this->assertFalse(Route::has('api.modules.dyn.x'));
        $this->getJson('/api/modules/dyn/x')->assertStatus(404);
    }

    #[Test]
    public function an_api_route_stripping_throttle_is_rejected(): void
    {
        $this->bootApi(fn () => Route::get('/x', fn () => 'OPEN')->withoutMiddleware('throttle:60,1')->name('x'));
        $this->assertFalse(Route::has('api.modules.dyn.x'));
    }

    #[Test]
    public function an_api_route_requires_a_key_by_default(): void
    {
        $this->bootApi(fn () => Route::get('/x', fn () => response()->json(['ok' => true]))->name('x'));
        $this->assertTrue(Route::has('api.modules.dyn.x'));
        $this->getJson('/api/modules/dyn/x')->assertStatus(401);
    }

    // ---- P2-8: read-only keys cannot write to module API routes ------------

    #[Test]
    public function a_read_only_key_cannot_write_to_a_module_api_route(): void
    {
        $this->bootApi(fn () => Route::post('/x', fn () => 'WROTE')->name('x'));

        ApiKey::create(['id' => 'ro-key', 'name' => 'ro', 'read_only' => true, 'created_at' => now()]);
        ApiKey::create(['id' => 'rw-key', 'name' => 'rw', 'read_only' => false, 'created_at' => now()]);
        $this->assertNotNull(ApiKey::find('ro-key'));

        $this->postJson('/api/modules/dyn/x', [], ['Authorization' => 'Bearer ro-key'])->assertStatus(403);
        $this->postJson('/api/modules/dyn/x', [], ['Authorization' => 'Bearer rw-key'])->assertStatus(200);
    }

    // ---- P2-3 / P2-4: scheduling isolation ---------------------------------

    #[Test]
    public function a_throwing_timing_closure_leaves_no_event_scheduled(): void
    {
        DynamicRouteModule::$register = fn ($r) => $r
            ->command(ExampleCommand::class)
            ->schedule(ExampleCommand::class, fn ($event) => throw new \RuntimeException('boom'));
        $this->bootModules([DynamicRouteModule::class], ['dyn']);

        $schedule = new Schedule;
        ModuleScheduler::schedule($schedule, app(ModuleRegistry::class));

        $this->assertCount(0, $schedule->events(), 'A failed timing closure must leave no scheduled event.');
    }

    #[Test]
    public function a_timing_closure_that_retargets_the_command_is_dropped(): void
    {
        DynamicRouteModule::$register = fn ($r) => $r
            ->command(ExampleCommand::class)
            ->schedule(ExampleCommand::class, fn ($event) => $event->command = 'php artisan migrate:fresh');
        $this->bootModules([DynamicRouteModule::class], ['dyn']);

        $schedule = new Schedule;
        ModuleScheduler::schedule($schedule, app(ModuleRegistry::class));

        $this->assertCount(0, $schedule->events());
    }

    #[Test]
    public function command_rejects_a_core_class(): void
    {
        $registrar = new ModuleRegistrar('dyn');

        $this->expectException(ModuleRegistrationException::class);
        $registrar->command(UserDelete::class);
    }

    // ---- P2-5 / P2-6: driver collisions and failures -----------------------

    #[Test]
    public function two_modules_claiming_one_driver_name_use_neither(): void
    {
        foreach ([[DriverModuleA::class, DriverModuleB::class], [DriverModuleB::class, DriverModuleA::class]] as $order) {
            $registry = app(ModuleRegistry::class);
            config(['modules.register' => $order, 'modules.safe_mode' => false]);
            $registry->flush();
            $registry->enable('drivera');
            $registry->enable('driverb');
            $registry->flush();

            $names = array_map(fn ($d) => $d['name'], $registry->drivers());
            $this->assertNotContains('shared', $names, 'A doubly-claimed driver name must not be used (' . implode(',', $order) . ').');
            $this->assertNotEmpty(array_filter($registry->errors(), fn ($e) => str_contains($e, 'shared')));
        }
    }

    #[Test]
    public function a_throwing_driver_factory_falls_back_to_no_op(): void
    {
        config(['vatsim.division_api_drivers' => ['boom' => \stdClass::class], 'vatsim.division_api_driver' => 'boom']);
        Facade::set('divisionApiEnabled', true);

        // stdClass is not a DivisionApiContract, so it must fall back to no-op.
        $this->assertInstanceOf(NoOpAdapter::class, app(DivisionApiContract::class));
    }

    // ---- P2-7: user:delete fail-closed -------------------------------------

    #[Test]
    public function a_module_declaring_data_with_no_handler_blocks_deletion(): void
    {
        $this->bootModules([DataHandlingNoHandlerModule::class]);

        $plan = app(ModuleRegistry::class)->privacyCleanupPlan();
        $this->assertArrayHasKey('datahandling', $plan['failed']);
    }

    // ---- P2-10 / P3: view namespace and skeleton ---------------------------

    #[Test]
    public function a_module_with_a_views_directory_exposes_a_view_path(): void
    {
        config(['app.version' => '7.9.9']);
        $this->bootModules(['ControlCenter\\Modules\\Skeleton\\SkeletonModule'], ['skeleton']);

        $this->assertNotNull(app(ModuleRegistry::class)->viewPath('skeleton'), 'the skeleton ships views under resources/views');
    }

    // ---- P3: core task type cannot be shadowed -----------------------------

    #[Test]
    public function a_module_cannot_shadow_a_core_task_type_key(): void
    {
        $this->bootModules([ExampleModule::class], ['example']);

        // ExampleModule registers 'example.review'; core keys stay core.
        $map = app(TaskTypeRegistry::class)->map();
        $this->assertSame(Custom::class, $map[Custom::KEY]);
    }

    // ---- P3: registrarFor guards shouldBoot --------------------------------

    #[Test]
    public function registrar_for_returns_null_for_a_disabled_module(): void
    {
        $this->bootModules([ExampleModule::class]); // discovered, not enabled

        $this->assertNull(app(ModuleRegistry::class)->registrarFor('example'));
    }

    // ---- P3: the skeleton module works end to end --------------------------

    #[Test]
    public function the_skeleton_module_loads_end_to_end(): void
    {
        config(['app.version' => '7.9.9']); // satisfy the skeleton's ^7.0 constraint

        $this->bootModules(['ControlCenter\\Modules\\Skeleton\\SkeletonModule'], ['skeleton']);

        $this->assertArrayHasKey('skeleton', app(ModuleRegistry::class)->discovered());
        $this->assertTrue(view()->exists('modules::skeleton.widget'), 'skeleton widget view should resolve');
        $this->assertTrue(view()->exists('modules::skeleton.index'), 'skeleton index view should resolve');
        $this->assertTrue(Route::has('modules.skeleton.index'), 'skeleton index route should be registered');
    }

    // ---- P2-7: installed.json-level fail-closed ----------------------------

    #[Test]
    public function two_packages_naming_the_same_class_block_deletion(): void
    {
        $this->withInstalledJson(function (array $data): array {
            $entry = [
                'type' => 'controlcenter-module',
                'extra' => ['controlcenter' => ['module' => 'Dup\\SameModule']],
            ];
            $data['packages'][] = ['name' => 'review/dup-a'] + $entry;
            $data['packages'][] = ['name' => 'review/dup-b'] + $entry;

            return $data;
        }, function (ModuleRegistry $registry): void {
            $failed = $registry->privacyCleanupPlan()['failed'];
            $this->assertNotEmpty(array_filter(array_keys($failed), fn ($k) => str_contains($k, 'review/dup-')));
        });
    }

    #[Test]
    public function a_missing_installed_json_blocks_deletion(): void
    {
        $path = base_path('vendor/composer/installed.json');
        if (! is_file($path)) {
            $this->markTestSkipped('installed.json not present.');
        }

        $backup = (string) file_get_contents($path);
        $moved = $path . '.moved';

        try {
            rename($path, $moved);
            $registry = app(ModuleRegistry::class);
            $registry->flush();

            $this->assertArrayHasKey('package:installed.json', $registry->privacyCleanupPlan()['failed']);
        } finally {
            if (is_file($moved)) {
                rename($moved, $path);
            } else {
                file_put_contents($path, $backup);
            }
            app(ModuleRegistry::class)->flush();
        }
    }

    /**
     * Run $assert with installed.json temporarily transformed by $mutate, then
     * restore it.
     */
    private function withInstalledJson(Closure $mutate, Closure $assert): void
    {
        $path = base_path('vendor/composer/installed.json');
        if (! is_file($path)) {
            $this->markTestSkipped('installed.json not present.');
        }

        $backup = (string) file_get_contents($path);

        try {
            $data = json_decode($backup, true, flags: JSON_THROW_ON_ERROR);
            file_put_contents($path, json_encode($mutate($data)));
            $registry = app(ModuleRegistry::class);
            $registry->flush();
            $assert($registry);
        } finally {
            file_put_contents($path, $backup);
            app(ModuleRegistry::class)->flush();
        }
    }
}
