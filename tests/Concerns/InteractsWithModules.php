<?php

namespace Tests\Concerns;

use App\Modules\ModuleRegistry;
use App\Modules\ModuleRouteLoader;
use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Helpers for tests that need modules booted inside an already-running
 * application. In production the module config is merged and gates are defined
 * during boot (before any request); under RefreshDatabase the modules table is
 * migrated after boot, so tests must re-run that wiring explicitly.
 */
trait InteractsWithModules
{
    /**
     * Register the given module classes, enable the named slugs, and re-run the
     * role-config merge and gate definitions so permissions resolve live.
     *
     * @param  array<int, class-string>  $moduleClasses
     * @param  array<int, string>  $enable
     */
    protected function bootModules(array $moduleClasses, array $enable = []): void
    {
        config(['modules.register' => $moduleClasses]);
        config(['modules.safe_mode' => false]);

        $registry = app(ModuleRegistry::class);
        $registry->flush();

        foreach ($enable as $slug) {
            $registry->enable($slug);
        }

        $registry->flush();

        // Re-merge module permissions/roles into config, mirroring boot,
        // without churning the container singleton.
        ModuleServiceProvider::mergeRoleConfig($registry);
        $registry->applyBindings();

        // Redefine gates so newly merged permissions resolve this request.
        foreach (config('roles.permissions', []) as $permission) {
            Gate::define($permission, fn ($user, $area = null) => $user->hasPermission($permission, $area));
        }

        // Register module routes, which the provider could not do at boot
        // because the modules table is migrated after the app boots in tests.
        (new ModuleRouteLoader($registry))->load();
    }
}
