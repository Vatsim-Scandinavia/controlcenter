<?php

namespace ControlCenter\ModuleApi;

use Closure;
use ControlCenter\ModuleApi\Navigation\NavigationItem;
use ControlCenter\ModuleApi\Privacy\PrivacyHandler;
use ControlCenter\ModuleApi\Settings\SettingsSchema;
use ControlCenter\ModuleApi\Ui\Slot;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;

/**
 * The single, stable surface a module uses to extend Control Center.
 *
 * Core passes a slug-scoped Registrar to {@see Module::register()}. Every
 * registration is attributed to the module and torn down when the module is
 * disabled, so a disabled module contributes nothing: no navigation, no
 * permissions, no routes, no scheduled work.
 *
 * Methods that accept names enforce the module's namespace where noted;
 * registering outside `modules.<slug>.*` throws.
 */
interface Registrar
{
    /**
     * The slug of the module this registrar is scoped to.
     */
    public function slug(): string;

    /**
     * Add one or more sidebar navigation entries.
     */
    public function navigation(NavigationItem ...$items): static;

    /**
     * Render a Blade view into a named UI slot (see {@see Slot}).
     *
     * @param  string  $slot  One of the stable slot names.
     * @param  string  $view  A Blade view name resolvable at render time.
     * @param  string|null  $permission  Permission required to see the slot item, or null for any authenticated user.
     * @param  array<string, mixed>  $data  Static data merged into the view.
     */
    public function slot(string $slot, string $view, ?string $permission = null, array $data = []): static;

    /**
     * Declare the schema for the module's admin settings page.
     */
    public function adminSettings(SettingsSchema $schema): static;

    /**
     * Declare the schema for fields shown on the per-user settings page.
     */
    public function userSettings(SettingsSchema $schema): static;

    /**
     * Register a functional permission. The name must live under
     * `modules.<slug>.*` or this throws.
     */
    public function permission(string $name, string $description): static;

    /**
     * Register a role contributed by the module and the permission patterns it
     * grants. The role key is namespaced to the module.
     *
     * @param  array<string, mixed>  $definition  Role definition (name, description, scope), mirroring config/roles.php.
     * @param  array<int, string>  $grants  Permission patterns the role grants (module-namespaced).
     */
    public function role(string $key, array $definition, array $grants = []): static;

    /**
     * Register a task type under a stable key. The class must extend Control
     * Center's task type base class.
     *
     * @param  class-string  $taskTypeClass
     */
    public function taskType(string $key, string $taskTypeClass): static;

    /**
     * Register the module's privacy handler.
     *
     * @param  class-string<PrivacyHandler>  $handlerClass
     */
    public function privacyHandler(string $handlerClass): static;

    /**
     * Register an Artisan command shipped by the module.
     *
     * @param  class-string  $commandClass
     */
    public function command(string $commandClass): static;

    /**
     * Register scheduled work. The callback receives the application schedule
     * and only runs while the module is enabled.
     *
     * @param  Closure(Schedule): void  $callback
     */
    public function schedule(Closure $callback): static;

    /**
     * Register an activity-log name so the module's audit entries render with a
     * proper label and subject link.
     */
    public function activityLogName(string $name, string $label): static;

    /**
     * Register an API scope. The name must live under `modules.<slug>.*`.
     */
    public function apiScope(string $name, string $description): static;

    /**
     * Register a named driver implementation for one of Control Center's
     * pluggable contracts (e.g. the division API).
     *
     * @param  class-string  $contract
     * @param  Closure(Application): object  $factory
     */
    public function driver(string $contract, string $name, Closure $factory): static;

    /**
     * Bind a service into the container while the module is enabled.
     *
     * @param  Closure(Application): mixed  $factory
     */
    public function bind(string $abstract, Closure $factory): static;

    /**
     * Register web routes. The callback defines routes with the usual Route
     * facade; core wraps them in a group prefixed `modules/<slug>`, named
     * `modules.<slug>.*`, with the authenticated web middleware stack and an
     * enabled-check that returns 404 while the module is disabled.
     *
     * @param  Closure(): void  $routes
     */
    public function web(Closure $routes): static;

    /**
     * Register API routes, grouped under `api/modules/<slug>` and named
     * `api.modules.<slug>.*`, guarded by the same enabled-check. These routes
     * are kept out of the public OpenAPI spec.
     *
     * @param  Closure(): void  $routes
     */
    public function api(Closure $routes): static;
}
