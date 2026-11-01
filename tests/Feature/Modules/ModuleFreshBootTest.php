<?php

namespace Tests\Feature\Modules;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\Fixtures\Modules\ExampleModule;
use Tests\Fixtures\Modules\ThrowingRouteModule;
use Tests\TestCase;

/**
 * Boot findings (1, 2, 3) are about what happens in a *real* fresh process:
 * whether enabled-state is resolved after the database is up, and whether
 * cached config leaks disabled modules' permissions. The InteractsWithModules
 * trait re-wires boot by hand and so cannot see these defects, so these tests
 * spawn actual `php artisan` subprocesses against a shared on-disk SQLite
 * database, with the fixture module registered via MODULES_REGISTER.
 *
 * To stay affordable, the schema is migrated once for the whole class and the
 * enabled state is toggled with direct SQLite writes; only the boot probes (the
 * thing under test) and `config:cache` run as fresh subprocesses.
 */
class ModuleFreshBootTest extends TestCase
{
    private static string $dbFile = '';

    private static string $cacheDir = '';

    /** @var array<string, string> */
    private array $env = [];

    private static function basePath(string $path = ''): string
    {
        // tests/Feature/Modules -> project root. Computed without the framework
        // because setUpBeforeClass runs before the application is created.
        return dirname(__DIR__, 3) . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    public static function setUpBeforeClass(): void
    {
        $tmp = sys_get_temp_dir() . '/cc_freshboot_' . bin2hex(random_bytes(6));
        self::$dbFile = $tmp . '.sqlite';
        self::$cacheDir = $tmp . '_cache';
        touch(self::$dbFile);
        @mkdir(self::$cacheDir, 0777, true);

        $migrate = new Process(
            ['php', 'artisan', 'migrate', '--force'],
            self::basePath(),
            self::baseEnv(),
            null,
            300,
        );
        $migrate->mustRun();
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$dbFile);
        @array_map('unlink', (array) glob(self::$cacheDir . '/*'));
        @rmdir(self::$cacheDir);
    }

    private function configCachePath(): string
    {
        return self::$cacheDir . '/config.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Guard: the subprocesses here must never write the shared config cache.
        // If a leftover exists it could silently change every other test's
        // config, so fail loudly rather than tolerate it.
        $this->assertFileDoesNotExist(
            self::basePath('bootstrap/cache/config.php'),
            'A cached config leaked into bootstrap/cache; it would affect other tests.'
        );

        @unlink($this->configCachePath());
        @unlink($this->routesCachePath());
        $this->env = self::baseEnv();
        $this->pdo()->exec('DELETE FROM modules');
    }

    protected function tearDown(): void
    {
        @unlink($this->configCachePath());
        @unlink($this->routesCachePath());

        parent::tearDown();
    }

    private function routesCachePath(): string
    {
        return self::$cacheDir . '/routes.php';
    }

    /** @return array<string, string> */
    private static function baseEnv(): array
    {
        return [
            'APP_ENV' => 'testing',
            'APP_KEY' => (string) getenv('APP_KEY'),
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => self::$dbFile,
            'DB_FOREIGN_KEYS' => 'false',
            'MODULES_REGISTER' => ExampleModule::class,
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            // Isolate every bootstrap cache path so config:cache (and any other
            // cache) writes into this test's temp dir, never the shared
            // bootstrap/cache that other tests boot from.
            'APP_CONFIG_CACHE' => self::$cacheDir . '/config.php',
            'APP_EVENTS_CACHE' => self::$cacheDir . '/events.php',
            'APP_PACKAGES_CACHE' => self::$cacheDir . '/packages.php',
            'APP_ROUTES_CACHE' => self::$cacheDir . '/routes.php',
            'APP_SERVICES_CACHE' => self::$cacheDir . '/services.php',
        ];
    }

    private function pdo(): PDO
    {
        $pdo = new PDO('sqlite:' . self::$dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    private function setEnabled(bool $enabled): void
    {
        $this->setEnabledSlugs($enabled ? ['example'] : []);
    }

    /**
     * Replace the enabled-module rows with the given slugs (all enabled).
     *
     * @param  array<int, string>  $slugs
     */
    private function setEnabledSlugs(array $slugs): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo()->exec('DELETE FROM modules');
        $insert = $this->pdo()->prepare('INSERT INTO modules (slug, enabled, enabled_at, created_at, updated_at) VALUES (?, 1, ?, ?, ?)');

        foreach ($slugs as $slug) {
            $insert->execute([$slug, $now, $now, $now]);
        }
    }

    /**
     * Boot a fresh process, handle a real HTTP request (so the module-route
     * middleware runs), and print a probe of the routing table: the core login
     * route, its URI (to catch a module prefix bleeding onto core routes), the
     * example module route, and the throwing module's partial route.
     *
     * Module routes are registered per request by middleware, never during the
     * console boot, so the probe must drive the HTTP kernel to observe them.
     *
     * @param  array<string, string>  $extraEnv
     */
    private function routeProbe(array $extraEnv = []): string
    {
        $php = <<<'PHP'
        $kernel = app(Illuminate\Contracts\Http\Kernel::class);
        $kernel->handle(Illuminate\Http\Request::create('/', 'GET'));
        $has = fn (string $name) => (int) Illuminate\Support\Facades\Route::has($name);
        echo 'LOGIN:' . $has('login');
        echo ';LOGINURI:' . (Illuminate\Support\Facades\Route::has('login') ? route('login', [], false) : '-');
        echo ';MODROUTE:' . $has('modules.example.index');
        echo ';MODHALF:' . $has('modules.throwroute.half');
        PHP;

        $process = new Process(
            ['php', 'artisan', 'tinker', '--execute', $php],
            self::basePath(),
            array_merge($this->env, $extraEnv),
            null,
            120,
        );
        $process->mustRun();

        return $process->getOutput();
    }

    /**
     * Run an Artisan command in a fresh process against this test's isolated
     * cache paths (never the shared bootstrap/cache).
     *
     * @param  array<int, string>  $command
     * @param  array<string, string>  $extraEnv
     */
    private function artisanProcess(array $command, array $extraEnv = []): void
    {
        $process = new Process(
            array_merge(['php', 'artisan'], $command),
            self::basePath(),
            array_merge($this->env, $extraEnv),
            null,
            120,
        );
        $process->mustRun();
    }

    private function cacheRoutes(array $extraEnv = []): void
    {
        $this->artisanProcess(['route:cache'], $extraEnv);
    }

    /**
     * Probe the routing table from inside a console command (tinker), without
     * handling an HTTP request. Module routes are registered on CommandStarting,
     * so route() and signed URLs must resolve here too.
     *
     * @param  array<string, string>  $extraEnv
     */
    private function consoleRouteProbe(array $extraEnv = []): string
    {
        $php = <<<'PHP'
        $has = (int) Illuminate\Support\Facades\Route::has('modules.example.index');
        echo 'MODROUTE:' . $has;
        echo ';URL:' . ($has ? route('modules.example.index', [], false) : '-');
        echo ';SIGNED:' . ($has && str_contains(Illuminate\Support\Facades\URL::signedRoute('modules.example.index'), 'signature') ? 1 : 0);
        PHP;

        // Console command events (which register module routes) are only wired
        // by the framework when NOT running under the testing environment, so a
        // non-testing APP_ENV exercises the real production path here.
        $process = new Process(
            ['php', 'artisan', 'tinker', '--execute', $php],
            self::basePath(),
            array_merge($this->env, ['APP_ENV' => 'local'], $extraEnv),
            null,
            120,
        );
        $process->mustRun();

        return $process->getOutput();
    }

    /**
     * Boot a fresh process and print a probe of the module permission's state.
     *
     * @param  array<string, string>  $extraEnv
     */
    private function probe(array $extraEnv = []): string
    {
        $php = <<<'PHP'
        $perms = app(App\Services\RoleSource::class)->permissions();
        echo in_array('modules.example.view', $perms, true) ? 'PERM:PRESENT' : 'PERM:ABSENT';
        echo Illuminate\Support\Facades\Gate::has('modules.example.view') ? ';GATE:YES' : ';GATE:NO';
        PHP;

        $process = new Process(
            ['php', 'artisan', 'tinker', '--execute', $php],
            self::basePath(),
            array_merge($this->env, $extraEnv),
            null,
            120,
        );
        $process->mustRun();

        return $process->getOutput();
    }

    #[Test]
    public function an_enabled_module_activates_in_a_fresh_process(): void
    {
        $this->setEnabled(true);

        $output = $this->probe();

        $this->assertStringContainsString('PERM:PRESENT', $output, $output);
        $this->assertStringContainsString('GATE:YES', $output, $output);
    }

    #[Test]
    public function a_disabled_module_stays_inert_in_a_fresh_process(): void
    {
        $this->setEnabled(false);

        $output = $this->probe();

        $this->assertStringContainsString('PERM:ABSENT', $output, $output);
        $this->assertStringContainsString('GATE:NO', $output, $output);
    }

    #[Test]
    public function disabling_after_config_cache_removes_the_permission(): void
    {
        // Finding 1: cache config while enabled, then disable. The cached config
        // must contain no module contribution, and a fresh process must not keep
        // the permission.
        $this->setEnabled(true);

        $cacheProcess = new Process(['php', 'artisan', 'config:cache'], self::basePath(), $this->env, null, 120);
        $cacheProcess->mustRun();

        // The cache is written to this test's isolated path (APP_CONFIG_CACHE),
        // never the shared bootstrap/cache.
        $cached = $this->configCachePath();
        $this->assertFileExists($cached);
        $this->assertFileDoesNotExist(self::basePath('bootstrap/cache/config.php'));
        $this->assertStringNotContainsString('modules.example', (string) file_get_contents($cached));

        $this->setEnabled(false);

        $output = $this->probe();

        $this->assertStringContainsString('PERM:ABSENT', $output, $output);
        $this->assertStringContainsString('GATE:NO', $output, $output);
    }

    #[Test]
    public function safe_mode_suppresses_an_enabled_module_in_a_fresh_process(): void
    {
        $this->setEnabled(true);

        $output = $this->probe(['MODULES_SAFE_MODE' => 'true']);

        $this->assertStringContainsString('PERM:ABSENT', $output, $output);
    }

    #[Test]
    public function a_throwing_module_route_does_not_corrupt_core_routes_on_boot(): void
    {
        // P2-2 + finding 1: a module whose route callback throws must leave
        // nothing behind — neither its partial routes nor its group attributes
        // bleeding onto core routes or later modules. ThrowingRouteModule is
        // registered first (it registers /half, then throws) and enabled.
        $register = ThrowingRouteModule::class . ',' . ExampleModule::class;

        $this->setEnabledSlugs(['throwroute', 'example']);

        $output = $this->routeProbe(['MODULES_REGISTER' => $register]);

        $this->assertStringContainsString('LOGIN:1', $output, $output);
        $this->assertStringContainsString('LOGINURI:/login', $output, $output);
        // The example module, registered after the throwing one, is unaffected.
        $this->assertStringContainsString('MODROUTE:1', $output, $output);
        // The throwing module's partial /half route must not have survived.
        $this->assertStringContainsString('MODHALF:0', $output, $output);
    }

    #[Test]
    public function an_enabled_module_route_is_registered_at_runtime_despite_a_stale_route_cache(): void
    {
        // Finding 4: build the route cache while the module is disabled, then
        // enable it. The module's route is never in the cache, but it must be
        // registered at runtime on top of the cached core routes.
        $this->setEnabled(false);
        $this->cacheRoutes();

        $this->assertFileExists($this->routesCachePath());
        $this->assertFileDoesNotExist(self::basePath('bootstrap/cache/routes-v7.php'));

        $this->setEnabled(true);

        $output = $this->routeProbe();

        $this->assertStringContainsString('LOGIN:1', $output, $output);
        $this->assertStringContainsString('MODROUTE:1', $output, $output);
    }

    #[Test]
    public function module_routes_are_never_written_into_the_route_cache(): void
    {
        // Finding 4: caching while enabled must not bake module routes into the
        // cache, so disabling later removes them (runtime registration is the
        // single source of truth, which is also multi-replica safe).
        $this->setEnabled(true);
        $this->cacheRoutes();

        $cached = (string) file_get_contents($this->routesCachePath());
        $this->assertStringNotContainsString('modules/example', $cached);

        // With the cache built while enabled, disabling must still drop the route.
        $this->setEnabled(false);

        $output = $this->routeProbe();

        $this->assertStringContainsString('MODROUTE:0', $output, $output);
    }

    #[Test]
    public function optimize_does_not_cache_module_routes(): void
    {
        // P2-3: `optimize` builds the route cache via a nested/programmatic call,
        // not a literal `route:cache` argv. The route cache must still exclude
        // module routes, because the cache build never handles an HTTP request.
        // Run under `local`, where console commands DO register module routes, so
        // the exclusion isn't merely a side effect of the testing environment.
        $this->setEnabled(true);
        $this->artisanProcess(['optimize'], ['APP_ENV' => 'local']);

        $cached = (string) file_get_contents($this->routesCachePath());
        $this->assertStringNotContainsString('modules/example', $cached, $cached);

        // Still served at runtime when enabled...
        $this->assertStringContainsString('MODROUTE:1', $this->routeProbe(), 'enabled route missing at runtime');

        // ...and gone once disabled, because the cache never captured it.
        $this->setEnabled(false);
        $this->assertStringContainsString('MODROUTE:0', $this->routeProbe());
    }

    #[Test]
    public function module_routes_resolve_in_a_console_command(): void
    {
        // A1: route() and URL::signedRoute() must work in a console command
        // (here, tinker), not only during an HTTP request.
        $this->setEnabled(true);

        $output = $this->consoleRouteProbe();

        $this->assertStringContainsString('MODROUTE:1', $output, $output);
        $this->assertStringContainsString('URL:/modules/example', $output, $output);
        $this->assertStringContainsString('SIGNED:1', $output, $output);
    }

    #[Test]
    public function a_disabled_module_registers_no_routes_in_a_console_command(): void
    {
        $this->setEnabled(false);

        $this->assertStringContainsString('MODROUTE:0', $this->consoleRouteProbe());
    }

    #[Test]
    public function safe_mode_registers_no_routes_in_a_console_command(): void
    {
        $this->setEnabled(true);

        $this->assertStringContainsString('MODROUTE:0', $this->consoleRouteProbe(['MODULES_SAFE_MODE' => 'true']));
    }

    #[Test]
    public function route_cache_built_in_a_console_command_excludes_module_routes(): void
    {
        // A1 corollary: even though console commands register module routes, the
        // route:cache build (a fresh app that runs no command) must not capture
        // them — so route() works at runtime but the cache file stays clean.
        $this->setEnabled(true);
        // Build the cache under the production-like env where console commands
        // DO register module routes — proving the fresh-app build still excludes
        // them (it runs no command) rather than the testing env masking it.
        $this->cacheRoutes(['APP_ENV' => 'local']);

        $this->assertStringNotContainsString('modules/example', (string) file_get_contents($this->routesCachePath()));
        $this->assertStringContainsString('MODROUTE:1', $this->consoleRouteProbe());
    }
}
