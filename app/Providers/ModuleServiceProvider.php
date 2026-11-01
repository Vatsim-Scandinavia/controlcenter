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
        (new ModuleRouteLoader($registry))->load();
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
