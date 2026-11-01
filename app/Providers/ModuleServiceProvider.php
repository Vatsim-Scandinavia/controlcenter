<?php

namespace App\Providers;

use App\Modules\ModuleRegistry;
use App\Modules\ModuleRouteLoader;
use App\Services\RoleSource;
use App\Tasks\TaskTypeRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the module system into the application.
 *
 * Nothing here mutates `config('roles.*')`: role and permission contributions
 * are read live through {@see RoleSource} by the permission matrix, the gate
 * definitions and the role UI. All work that depends on the database (resolving
 * which modules are enabled, then applying their bindings, commands, routes and
 * migrations) happens in {@see boot()}, which runs after the database is
 * initialised — register() must not touch the database.
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

        $registry->applyBindings();
        $this->registerCommands($registry);
        $this->registerMigrations($registry);

        // Register module routes at runtime, after the core routes have loaded.
        //
        // The framework's RouteServiceProvider registers its route loading in a
        // booted callback during the *register* phase; registering ours in the
        // *boot* phase queues it afterwards, so it runs once the core routes
        // (cached or from files) are in place. This matters for a cached
        // instance, where the compiled route collection replaces anything
        // registered earlier in boot.
        //
        // Routes are deliberately not registered while building the route cache,
        // so module routes never enter it: the live enabled state is the single
        // source of truth on every request, which keeps enable/disable effective
        // immediately and is safe across replicas.
        $this->app->booted(function () use ($registry): void {
            if ($this->isBuildingRouteCache()) {
                return;
            }

            (new ModuleRouteLoader($registry))->load();
        });
    }

    /**
     * Whether the current process is building the route cache (`route:cache`),
     * during which module routes must not be registered so they never land in
     * the cached collection.
     */
    private function isBuildingRouteCache(): bool
    {
        return $this->app->runningInConsole()
            && in_array('route:cache', (array) ($_SERVER['argv'] ?? []), true);
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
