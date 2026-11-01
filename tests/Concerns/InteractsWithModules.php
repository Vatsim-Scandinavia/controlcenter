<?php

namespace Tests\Concerns;

use App\Modules\ModuleRegistry;
use App\Modules\ModuleRouteLoader;
use App\Services\RoleSource;
use Illuminate\Support\Facades\Gate;

/**
 * Helpers for feature tests that exercise module extension points inside an
 * already-booted application.
 *
 * This does NOT simulate a real boot — permission/role contributions are read
 * live from {@see RoleSource}, so enabling a module is visible to the permission
 * matrix immediately. It re-does only the two things the framework does once at
 * boot and cannot repeat mid-test under RefreshDatabase (the modules table is
 * migrated after the app boots): defining gates for module permissions, and
 * registering the enabled modules' routes. The genuine "does it wire up on a
 * fresh process" question is covered by the fresh-process boot tests, not here.
 */
trait InteractsWithModules
{
    /**
     * Register the given module classes and enable the named slugs.
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

        // Define gates for module permissions (AuthServiceProvider does this at
        // boot, before the modules table exists under RefreshDatabase).
        foreach (app(RoleSource::class)->permissions() as $permission) {
            Gate::define($permission, fn ($user, $area = null) => $user->hasPermission($permission, $area));
        }

        // Register the enabled modules' routes (ModuleServiceProvider does this
        // at boot).
        (new ModuleRouteLoader($registry))->load();
    }
}
