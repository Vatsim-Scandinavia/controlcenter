<?php

namespace App\Providers;

use App\Modules\ModuleRegistry;
use App\Modules\ModuleRouteLoader;
use App\Services\RoleSource;
use App\Tasks\TaskTypeRegistry;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the module system into the application.
 *
 * Nothing here mutates `config('roles.*')`: role and permission contributions
 * are read live through {@see RoleSource} by the permission matrix, the gate
 * definitions and the role UI. All work that depends on the database (resolving
 * which modules are enabled, then applying their bindings, commands and
 * migrations) happens in {@see boot()}, which runs after the database is
 * initialised — register() must not touch the database. Module routes are the
 * exception: they are registered per request (see the boot() note), not here.
 */
class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(RoleSource::class);
        $this->app->singleton(TaskTypeRegistry::class);
    }

    public function boot(): void
    {
        /** @var ModuleRegistry $registry */
        $registry = $this->app->make(ModuleRegistry::class);

        $this->registerCommands($registry);
        $this->registerMigrations($registry);
        $this->registerViews($registry);
        $this->registerConsoleRoutes($registry);

        // Module routes are NOT registered here for HTTP. They are registered
        // per request by App\Http\Middleware\RegisterModuleRoutes, so they are
        // available at runtime (cached core routes or not) yet never captured by
        // the route cache (which is always built without handling an HTTP
        // request). The console path is handled by registerConsoleRoutes().
    }

    /**
     * Register the `modules::` view namespace for every booted module from its
     * install path, so a module ships Blade views (referenced as
     * `modules::<slug>.<view>`) without needing its own service provider — which
     * would boot even while the module is disabled or in safe mode.
     */
    private function registerViews(ModuleRegistry $registry): void
    {
        foreach ($registry->booted() as $slug => $registrar) {
            $hint = $registry->viewPath($slug);

            if ($hint !== null) {
                $this->loadViewsFrom($hint, 'modules');
            }
        }
    }

    /**
     * Register module routes for console runs so route() and signed URLs resolve
     * in commands, scheduled tasks and sync jobs dispatched from the console.
     *
     * This hangs off CommandStarting rather than boot(): the route-cache build
     * (route:cache / optimize / a programmatic call) bootstraps a fresh
     * application but runs no command inside it, so the listener never fires
     * there and module routes are never serialised into the cache.
     */
    private function registerConsoleRoutes(ModuleRegistry $registry): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        Event::listen(CommandStarting::class, static function () use ($registry): void {
            (new ModuleRouteLoader($registry))->load();
        });
    }

    /**
     * Register module-shipped migration paths so `php artisan migrate`
     * discovers each compatible module's tables, wherever the module is
     * installed (in-repo under `modules/` or as a Composer package in `vendor/`).
     */
    private function registerMigrations(ModuleRegistry $registry): void
    {
        $paths = $registry->migrationPaths();

        if ($paths !== []) {
            $this->loadMigrationsFrom($paths);
        }
    }

    private function registerCommands(ModuleRegistry $registry): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $commands = [];

        foreach ($registry->booted() as $registrar) {
            array_push($commands, ...$registrar->commands);
        }

        if ($commands !== []) {
            $this->commands($commands);
        }
    }
}
