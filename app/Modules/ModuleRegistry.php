<?php

namespace App\Modules;

use App\Models\Module as ModuleRecord;
use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\Navigation\NavigationItem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Discovers installed modules, tracks which are enabled and compatible, and
 * exposes everything the enabled modules have registered.
 *
 * Boot is defensive by design: a missing `modules` table (fresh install before
 * `migrate`), an enabled module whose package has been removed, or a module
 * that throws while registering must never take the whole application down.
 * Problems are logged and the offending module is skipped.
 */
class ModuleRegistry
{
    /** @var array<string, DiscoveredModule>|null */
    private ?array $discovered = null;

    /** @var array<string, ModuleRegistrar> */
    private array $registrars = [];

    /** @var array<int, string> */
    private array $errors = [];

    public function __construct(private Application $app) {}

    /**
     * Whether modules are globally suppressed for recovery.
     */
    public function safeMode(): bool
    {
        return (bool) config('modules.safe_mode', false);
    }

    /**
     * All installed modules keyed by slug, regardless of enabled state.
     *
     * @return array<string, DiscoveredModule>
     */
    public function discovered(): array
    {
        if ($this->discovered !== null) {
            return $this->discovered;
        }

        $enabledSlugs = $this->enabledSlugs();
        $discovered = [];

        foreach ($this->candidateClasses() as $class => $meta) {
            $source = $meta['source'];

            try {
                /** @var Module $instance */
                $instance = $this->app->make($class);
                $manifest = $instance->manifest();
                $slug = $manifest->slug;

                $discovered[$slug] = new DiscoveredModule(
                    class: $class,
                    manifest: $manifest,
                    enabled: ! $this->safeMode() && in_array($slug, $enabledSlugs, true),
                    compatible: CcVersion::satisfies($manifest->ccVersion),
                    source: $source,
                    installPath: $meta['installPath'],
                );
            } catch (Throwable $e) {
                $this->errors[] = "Failed to load module [{$class}] from {$source}: {$e->getMessage()}";
                Log::warning("Module discovery skipped [{$class}].", ['exception' => $e, 'source' => $source]);
            }
        }

        return $this->discovered = $discovered;
    }

    /**
     * The registrar for a discovered module, with the module's register() hook
     * run. Registrars are built for every discovered module (so disabled
     * modules still expose privacy handlers) and cached. A module that throws
     * while registering is isolated and contributes nothing.
     */
    public function registrarFor(string $slug): ?ModuleRegistrar
    {
        if (array_key_exists($slug, $this->registrars)) {
            return $this->registrars[$slug] ?: null;
        }

        $module = $this->discovered()[$slug] ?? null;

        if ($module === null) {
            return null;
        }

        try {
            /** @var Module $instance */
            $instance = $this->app->make($module->class);
            $registrar = new ModuleRegistrar($slug);
            $instance->register($registrar);

            return $this->registrars[$slug] = $registrar;
        } catch (Throwable $e) {
            $this->report($slug, $e, 'registering');
            $this->registrars[$slug] = null;

            return null;
        }
    }

    /**
     * Registrars for every module that should boot (enabled and compatible).
     *
     * @return array<string, ModuleRegistrar>
     */
    public function booted(): array
    {
        $booted = [];

        foreach ($this->discovered() as $slug => $module) {
            if (! $module->shouldBoot()) {
                continue;
            }

            if ($registrar = $this->registrarFor($slug)) {
                $booted[$slug] = $registrar;
            }
        }

        return $booted;
    }

    /**
     * Navigation items contributed by booted modules, authorization unresolved,
     * sorted by their declared order.
     *
     * @return array<int, NavigationItem>
     */
    public function navigation(): array
    {
        $items = [];

        foreach ($this->booted() as $registrar) {
            array_push($items, ...$registrar->navigation);
        }

        usort($items, fn (NavigationItem $a, NavigationItem $b): int => $a->order <=> $b->order);

        return $items;
    }

    /**
     * Slot entries registered for the given slot by booted modules.
     *
     * @return array<int, array{slug: string, slot: string, view: string, permission: ?string, data: array<string, mixed>}>
     */
    public function slot(string $slot): array
    {
        $entries = [];

        foreach ($this->booted() as $slug => $registrar) {
            foreach ($registrar->slots as $entry) {
                if ($entry['slot'] === $slot) {
                    $entries[] = ['slug' => $slug] + $entry;
                }
            }
        }

        return $entries;
    }

    /**
     * Permissions registered by booted modules, as name => description.
     *
     * @return array<string, string>
     */
    public function permissions(): array
    {
        return $this->mergeFromBooted(fn (ModuleRegistrar $r) => $r->permissions);
    }

    /**
     * Role definitions registered by booted modules.
     *
     * @return array<string, array{definition: array<string, mixed>, grants: array<int, string>}>
     */
    public function roles(): array
    {
        return $this->mergeFromBooted(fn (ModuleRegistrar $r) => $r->roles);
    }

    /**
     * Task types registered by booted modules, as key => class.
     *
     * @return array<string, class-string>
     */
    public function taskTypes(): array
    {
        return $this->mergeFromBooted(fn (ModuleRegistrar $r) => $r->taskTypes);
    }

    /**
     * Activity log names registered by booted modules, as name => label.
     *
     * @return array<string, string>
     */
    public function activityLogNames(): array
    {
        return $this->mergeFromBooted(fn (ModuleRegistrar $r) => $r->activityLogNames);
    }

    /**
     * API scopes registered by booted modules, as name => description.
     *
     * @return array<string, string>
     */
    public function apiScopes(): array
    {
        return $this->mergeFromBooted(fn (ModuleRegistrar $r) => $r->apiScopes);
    }

    /**
     * Drivers registered by booted modules.
     *
     * @return array<int, array{slug: string, contract: class-string, name: string, factory: \Closure}>
     */
    public function drivers(): array
    {
        $drivers = [];

        foreach ($this->booted() as $slug => $registrar) {
            foreach ($registrar->drivers as $driver) {
                $drivers[] = ['slug' => $slug] + $driver;
            }
        }

        return $drivers;
    }

    /**
     * Privacy handlers for every *installed* module (enabled or not), because a
     * disabled module's user data still exists and must be handled.
     *
     * @return array<string, array<int, class-string>>
     */
    public function privacyHandlers(): array
    {
        return $this->privacyCleanupPlan()['handlers'];
    }

    /**
     * The privacy cleanup plan for a user-data action: the handlers of every
     * installed module, plus the slugs of modules that could not be initialised.
     *
     * A module whose register() hook throws contributes no handlers, but we
     * cannot conclude it holds no user data — so its failure is surfaced
     * separately rather than silently treated as "nothing to clean". Callers
     * (e.g. user:delete) use this to refuse to report complete deletion when a
     * module could not be asked to clean up.
     *
     * @return array{handlers: array<string, array<int, class-string>>, failed: array<string, string>}
     */
    public function privacyCleanupPlan(): array
    {
        $handlers = [];
        $failed = [];

        foreach ($this->discovered() as $slug => $module) {
            $registrar = $this->registrarFor($slug);

            if ($registrar === null) {
                // register() threw: the module is installed but could not be
                // initialised, so whether it holds this user's data is unknown.
                $failed[$slug] = "Module [{$slug}] could not be initialised, so its data could not be removed.";

                continue;
            }

            if ($registrar->privacyHandlers !== []) {
                $handlers[$slug] = $registrar->privacyHandlers;
            }
        }

        return ['handlers' => $handlers, 'failed' => $failed];
    }

    /**
     * Apply booted modules' container-level registrations (bindings, drivers,
     * commands). Navigation, slots, settings, permissions and roles are read
     * lazily by their consumers and are not applied here.
     */
    public function applyBindings(): void
    {
        foreach ($this->booted() as $registrar) {
            foreach ($registrar->bindings as $abstract => $factory) {
                $this->app->bind($abstract, $factory);
            }
        }
    }

    /**
     * Database migration paths contributed by discovered, compatible modules.
     *
     * A bundled module may ship migrations in `modules/<slug>/database/migrations`;
     * when that directory exists it is registered so `php artisan migrate` (and
     * the test migrator) discovers the module's tables. Migrations are exposed
     * for every compatible module regardless of enabled state, because a
     * disabled module's data tables must continue to exist.
     *
     * @return array<int, string>
     */
    public function migrationPaths(): array
    {
        $paths = [];

        foreach ($this->discovered() as $slug => $module) {
            if (! $module->compatible) {
                continue;
            }

            // In-repo modules follow the modules/<slug> convention; Composer
            // packages (the primary distribution model) live under vendor/ and
            // are discovered relative to their install path.
            $candidates = [
                $this->app->basePath("modules/{$slug}/database/migrations"),
            ];

            if ($module->installPath !== null) {
                $candidates[] = $module->installPath . '/database/migrations';
            }

            foreach ($candidates as $path) {
                if (is_dir($path)) {
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function isEnabled(string $slug): bool
    {
        return ($this->discovered()[$slug] ?? null)?->enabled ?? false;
    }

    public function enable(string $slug): void
    {
        $module = $this->discovered()[$slug] ?? null;

        ModuleRecord::query()->updateOrCreate(
            ['slug' => $slug],
            ['enabled' => true, 'enabled_at' => now(), 'installed_version' => $module?->manifest->version],
        );

        $this->flush();
    }

    public function disable(string $slug): void
    {
        ModuleRecord::query()->updateOrCreate(
            ['slug' => $slug],
            ['enabled' => false],
        );

        $this->flush();
    }

    /**
     * Forget cached discovery/registration state so a subsequent call re-reads
     * enabled state from the database.
     */
    public function flush(): void
    {
        $this->discovered = null;
        $this->registrars = [];
        $this->errors = [];
    }

    /**
     * @template T of array<array-key, mixed>
     *
     * @param  callable(ModuleRegistrar): T  $extractor
     * @return T
     */
    private function mergeFromBooted(callable $extractor): array
    {
        $merged = [];

        foreach ($this->booted() as $registrar) {
            $merged += $extractor($registrar);
        }

        return $merged;
    }

    /**
     * Slugs enabled in the database. Tolerates the table being absent (fresh
     * install) or the database being unreachable (e.g. config:cache on build).
     *
     * @return array<int, string>
     */
    private function enabledSlugs(): array
    {
        try {
            return ModuleRecord::query()->where('enabled', true)->pluck('slug')->all();
        } catch (Throwable $e) {
            Log::debug('Module enabled state unavailable; treating all modules as disabled.', ['reason' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Discover candidate module classes from the bundled list and any installed
     * Composer packages of type `controlcenter-module`.
     *
     * @return array<class-string, array{source: string, installPath: string|null}>
     */
    private function candidateClasses(): array
    {
        $classes = [];

        foreach ((array) config('modules.register', []) as $class) {
            if (is_string($class) && $class !== '') {
                $classes[$class] = ['source' => 'bundled', 'installPath' => null];
            }
        }

        foreach ($this->composerModuleClasses() as $class => $package) {
            $classes[$class] = ['source' => "package {$package['name']}", 'installPath' => $package['installPath']];
        }

        return $classes;
    }

    /**
     * @return array<class-string, array{name: string, installPath: string|null}>
     */
    private function composerModuleClasses(): array
    {
        $path = $this->app->basePath('vendor/composer/installed.json');

        if (! is_file($path)) {
            return [];
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        $packages = $data['packages'] ?? $data;
        $classes = [];

        foreach ((array) $packages as $package) {
            if (($package['type'] ?? null) !== 'controlcenter-module') {
                continue;
            }

            $class = $package['extra']['controlcenter']['module'] ?? null;

            if (is_string($class) && $class !== '') {
                $installPath = null;

                if (isset($package['install-path']) && is_string($package['install-path'])) {
                    // install-path is relative to vendor/composer/.
                    $resolved = realpath($this->app->basePath('vendor/composer/' . $package['install-path']));
                    $installPath = $resolved !== false ? $resolved : null;
                }

                $classes[$class] = ['name' => $package['name'] ?? 'unknown', 'installPath' => $installPath];
            }
        }

        return $classes;
    }

    private function report(string $slug, Throwable $e, string $phase): void
    {
        $message = "Module [{$slug}] failed while {$phase}: {$e->getMessage()}";
        $this->errors[] = $message;

        Log::error($message, ['exception' => $e, 'module' => $slug]);

        self::captureToSentry($e, $slug);
    }

    /**
     * Report a module-origin throwable to Sentry, tagged with the offending
     * module so failures in module code (boot, registration, slots, commands)
     * are attributable to the module rather than the core application.
     */
    public static function captureToSentry(Throwable $e, string $slug): void
    {
        if (! app()->bound('sentry')) {
            return;
        }

        $sentry = app('sentry');

        if (method_exists($sentry, 'configureScope')) {
            $sentry->configureScope(function ($scope) use ($slug): void {
                $scope->setTag('module', $slug);
            });
        }

        $sentry->captureException($e);
    }
}
