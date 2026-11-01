<?php

namespace ControlCenter\ModuleApi;

use Closure;
use ControlCenter\ModuleApi\Navigation\NavigationItem;
use ControlCenter\ModuleApi\Settings\SettingsSchema;
use ControlCenter\ModuleApi\Ui\Slot;
use Illuminate\Console\Scheduling\Event;
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
     * Register an Artisan command shipped by the module.
     *
     * @param  class-string  $commandClass
     */
    public function command(string $commandClass): static;

    /**
     * Schedule one of the module's own commands while the module is enabled.
     *
     * The command class must also be registered by this module via
     * {@see command()}. Core creates the scheduled event for that command; the
     * timing closure only configures frequency and constraints on it (e.g.
     * `fn ($event) => $event->dailyAt('02:00')->withoutOverlapping()`). A module
     * cannot schedule an arbitrary command or reach the application-wide
     * schedule.
     *
     * @param  class-string  $commandClass
     * @param  Closure(Event): void  $timing
     */
    public function schedule(string $commandClass, Closure $timing): static;

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
     * pluggable contracts. Only an explicitly allow-listed contract may be
     * driven (today just the division API, `App\Contracts\DivisionApiContract`);
     * any other contract throws. The name must not collide with a built-in
     * driver or another module's driver.
     *
     * @param  class-string  $contract
     * @param  Closure(Application): object  $factory
     */
    public function driver(string $contract, string $name, Closure $factory): static;

    /**
     * Register web routes. The callback defines routes with the usual Route
     * facade; core wraps them in a group prefixed `modules/<slug>`, named
     * `modules.<slug>.*`, with the authenticated web middleware stack and an
     * enabled-check that returns 404 while the module is disabled.
     *
     * Core *enforces* that stack by resolving each new route's effective
     * middleware and requiring the mandatory classes to survive, requiring the
     * URI and name to stay under the module's prefix, and rejecting collisions
     * with existing routes. Any route that evades this (stripping middleware by
     * alias, class or parent class, `setAction`, `setUri`, a raw collection add,
     * a prefix/name escape, …) rejects the whole module's routes. This is a
     * contract, not a sandbox: a module's own route-level middleware is honoured
     * and the module's code can still call the framework directly. Existing
     * route names (e.g. `route('login')`) remain resolvable inside the callback.
     * A webhook that must skip CSRF cannot be served here — use api().
     *
     * @param  Closure(): void  $routes
     */
    public function web(Closure $routes): static;

    /**
     * Register API routes, grouped under `api/modules/<slug>`, named
     * `api.modules.<slug>.*`, and kept out of the public OpenAPI spec.
     *
     * The group requires API-token authentication and throttling by default, so
     * a module API route needs a valid key even if its author forgets; declare
     * per-route scopes with `->middleware('api-token:modules.<slug>.<scope>')`
     * and they stack on top. Read-only keys are refused on unsafe methods.
     * The same enforcement as web() applies (see above).
     *
     * @param  Closure(): void  $routes
     */
    public function api(Closure $routes): static;
}
