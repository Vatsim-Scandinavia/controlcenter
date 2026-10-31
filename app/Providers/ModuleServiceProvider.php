<?php

namespace App\Providers;

use App\Modules\ModuleRegistry;
use App\Modules\ModuleRouteLoader;
use App\Tasks\TaskTypeRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the module system into the application.
 *
 * Module permissions and roles are merged into the `roles.*` config at
 * register() time, before {@see AuthServiceProvider} defines gates, so existing
 * consumers that read `config('roles.*')` transparently see the contributions
 * of enabled modules. Disabled modules contribute nothing.
 *
 * Boot is defensive: everything delegates to {@see ModuleRegistry}, which
 * isolates discovery and per-module registration failures.
 */
class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(TaskTypeRegistry::class);

        $registry = $this->app->make(ModuleRegistry::class);

        self::mergeRoleConfig($registry);
        $registry->applyBindings();
    }

    public function boot(): void
    {
        /** @var ModuleRegistry $registry */
        $registry = $this->app->make(ModuleRegistry::class);

        $this->registerCommands($registry);
        $this->registerMigrations($registry);
        (new ModuleRouteLoader($registry))->load();
    }

    /**
     * Merge module-registered permissions and roles into the role config so the
     * permission matrix, gates and role UI see them without any module-specific
     * knowledge.
     */
    public static function mergeRoleConfig(ModuleRegistry $registry): void
    {
        $permissions = config('roles.permissions', []);
        $roles = config('roles.roles', []);
        $matrix = config('roles.matrix', []);

        foreach ($registry->permissions() as $name => $description) {
            $permissions[] = $name;
        }

        foreach ($registry->roles() as $key => $role) {
            $roles[$key] = $role['definition'];
            $matrix[$key] = $role['grants'];
        }

        config([
            'roles.permissions' => array_values(array_unique($permissions)),
            'roles.roles' => $roles,
            'roles.matrix' => $matrix,
        ]);
    }

    /**
     * Register module-shipped migration paths so `php artisan migrate`
     * discovers each compatible module's tables.
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
