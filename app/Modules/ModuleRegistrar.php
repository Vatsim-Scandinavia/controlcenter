<?php

namespace App\Modules;

use App\Modules\Exceptions\ModuleRegistrationException;
use Closure;
use ControlCenter\ModuleApi\Navigation\NavigationItem;
use ControlCenter\ModuleApi\Registrar;
use ControlCenter\ModuleApi\Settings\SettingsSchema;

/**
 * Slug-scoped {@see Registrar} implementation. One instance per enabled module;
 * it collects the module's registrations so the registry can apply them. Every
 * entry is attributed to the module's slug, so disabling the module drops all
 * of its contributions at once.
 */
class ModuleRegistrar implements Registrar
{
    /** @var array<int, NavigationItem> */
    public array $navigation = [];

    /** @var array<int, array{slot: string, view: string, permission: ?string, data: array<string, mixed>}> */
    public array $slots = [];

    public ?SettingsSchema $adminSettings = null;

    public ?SettingsSchema $userSettings = null;

    /** @var array<string, string> */
    public array $permissions = [];

    /** @var array<string, array{definition: array<string, mixed>, grants: array<int, string>}> */
    public array $roles = [];

    /** @var array<string, class-string> */
    public array $taskTypes = [];

    /** @var array<int, class-string> */
    public array $privacyHandlers = [];

    /** @var array<int, class-string> */
    public array $commands = [];

    /** @var array<int, Closure> */
    public array $schedules = [];

    /** @var array<string, string> */
    public array $activityLogNames = [];

    /** @var array<string, string> */
    public array $apiScopes = [];

    /** @var array<int, array{contract: class-string, name: string, factory: Closure}> */
    public array $drivers = [];

    /** @var array<string, Closure> */
    public array $bindings = [];

    /** @var array<int, Closure> */
    public array $webRoutes = [];

    /** @var array<int, Closure> */
    public array $apiRoutes = [];

    public function __construct(private string $slug) {}

    public function slug(): string
    {
        return $this->slug;
    }

    public function navigation(NavigationItem ...$items): static
    {
        foreach ($items as $item) {
            $this->navigation[] = $item;
        }

        return $this;
    }

    public function slot(string $slot, string $view, ?string $permission = null, array $data = []): static
    {
        $this->slots[] = compact('slot', 'view', 'permission', 'data');

        return $this;
    }

    public function adminSettings(SettingsSchema $schema): static
    {
        $this->adminSettings = $schema;

        return $this;
    }

    public function userSettings(SettingsSchema $schema): static
    {
        $this->userSettings = $schema;

        return $this;
    }

    public function permission(string $name, string $description): static
    {
        $this->assertNamespaced($name, 'permission');
        $this->permissions[$name] = $description;

        return $this;
    }

    public function role(string $key, array $definition, array $grants = []): static
    {
        $namespaced = str_starts_with($key, "modules.{$this->slug}.") ? $key : "modules.{$this->slug}.{$key}";

        foreach ($grants as $grant) {
            $this->assertNamespaced(ltrim($grant, '!'), 'role grant');
        }

        $this->roles[$namespaced] = compact('definition', 'grants');

        return $this;
    }

    public function taskType(string $key, string $taskTypeClass): static
    {
        $this->taskTypes[$key] = $taskTypeClass;

        return $this;
    }

    public function privacyHandler(string $handlerClass): static
    {
        $this->privacyHandlers[] = $handlerClass;

        return $this;
    }

    public function command(string $commandClass): static
    {
        $this->commands[] = $commandClass;

        return $this;
    }

    public function schedule(Closure $callback): static
    {
        $this->schedules[] = $callback;

        return $this;
    }

    public function activityLogName(string $name, string $label): static
    {
        $this->assertNamespaced($name, 'activity log name', allowRoot: true);
        $this->activityLogNames[$name] = $label;

        return $this;
    }

    public function apiScope(string $name, string $description): static
    {
        $this->assertNamespaced($name, 'API scope');
        $this->apiScopes[$name] = $description;

        return $this;
    }

    public function driver(string $contract, string $name, Closure $factory): static
    {
        $this->drivers[] = compact('contract', 'name', 'factory');

        return $this;
    }

    public function bind(string $abstract, Closure $factory): static
    {
        $this->bindings[$abstract] = $factory;

        return $this;
    }

    public function web(Closure $routes): static
    {
        $this->webRoutes[] = $routes;

        return $this;
    }

    public function api(Closure $routes): static
    {
        $this->apiRoutes[] = $routes;

        return $this;
    }

    /**
     * Enforce that a registered name lives under the module's namespace. This is
     * what stops one module from granting itself core permissions.
     */
    private function assertNamespaced(string $name, string $kind, bool $allowRoot = false): void
    {
        $root = "modules.{$this->slug}";

        // Permissions, API scopes and role grants must name something *beneath*
        // the root (`modules.<slug>.something`); the bare root alone is never a
        // valid permission. Activity-log categories legitimately use the bare
        // root, so they pass $allowRoot = true.
        $valid = str_starts_with($name, "{$root}.") || ($allowRoot && $name === $root);

        if (! $valid) {
            $expected = $allowRoot ? "{$root} or {$root}.*" : "{$root}.*";

            throw new ModuleRegistrationException(
                "Module [{$this->slug}] tried to register {$kind} [{$name}] outside its namespace [{$expected}]."
            );
        }
    }
}
