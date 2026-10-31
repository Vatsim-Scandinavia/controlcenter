<?php

namespace ControlCenter\ModuleApi\Navigation;

/**
 * A single sidebar navigation entry contributed by a module.
 *
 * Authorization is expressed as a permission name (or null for any
 * authenticated user); core resolves it through the gate and hides the item
 * when the current user lacks it.
 */
final class NavigationItem
{
    /**
     * @param  string  $title  Label shown in the sidebar.
     * @param  string  $route  Named route the item links to (typically `modules.<slug>.*`).
     * @param  string|null  $permission  Permission required to see the item, or null for any authenticated user.
     * @param  string|null  $icon  Optional Font Awesome icon class (e.g. `fa-plug`).
     * @param  array<string, mixed>  $routeParameters  Parameters passed to the named route.
     * @param  string|null  $activeWhen  Optional route-name pattern used to mark the item active (defaults to the route name).
     * @param  int  $order  Sort order within the module navigation section; lower sorts first.
     */
    public function __construct(
        public string $title,
        public string $route,
        public ?string $permission = null,
        public ?string $icon = null,
        public array $routeParameters = [],
        public ?string $activeWhen = null,
        public int $order = 100,
    ) {}
}
