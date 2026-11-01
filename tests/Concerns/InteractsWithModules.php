<?php

namespace Tests\Concerns;

use App\Modules\ModuleRegistry;
use App\Modules\ModuleRouteLoader;
use App\Services\RoleSource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;

/**
 * Helpers for feature tests that exercise module extension points inside an
 * already-booted application.
 *
 * This does NOT simulate a real boot — permission/role contributions are read
 * live from {@see RoleSource}, so enabling a module is visible to the permission
 * matrix immediately. It re-does the things the framework does once at boot and
 * cannot repeat mid-test under RefreshDatabase (the modules table is migrated
 * after the app boots): defining gates for module permissions, registering the
 * enabled modules' routes, and registering their view namespaces. The genuine
 * "does it wire up on a fresh process" question is covered by the fresh-process
 * boot tests, not here.
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

        // Register the enabled modules' routes and view namespaces
        // (ModuleServiceProvider does both at boot).
        (new ModuleRouteLoader($registry))->load();

        foreach (array_keys($registry->booted()) as $slug) {
            if (($hint = $registry->viewPath($slug)) !== null) {
                View::prependNamespace('modules', $hint);
            }
        }
    }
}
