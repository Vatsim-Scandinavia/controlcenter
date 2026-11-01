<?php

namespace Tests\Feature\Modules;

use PDO;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\Fixtures\Modules\ExampleModule;
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
        $this->env = self::baseEnv();
        $this->pdo()->exec('DELETE FROM modules');
    }

    protected function tearDown(): void
    {
        @unlink($this->configCachePath());

        parent::tearDown();
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
        $now = date('Y-m-d H:i:s');
        $this->pdo()->exec('DELETE FROM modules');
        $this->pdo()->prepare('INSERT INTO modules (slug, enabled, enabled_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?)')
            ->execute(['example', $enabled ? 1 : 0, $now, $now, $now]);
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
}
