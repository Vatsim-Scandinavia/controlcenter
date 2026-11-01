<?php

namespace App\Services;

use App\Modules\ModuleRegistry;

/**
 * The single, live source of roles and permissions: the pristine core config
 * merged with the contributions of currently-enabled modules.
 *
 * This never mutates `config('roles.*')`. Reading live on each call means a
 * module disabled after `config:cache` contributes nothing on the next boot
 * (the cache only ever holds the pristine core config), and a module enabled at
 * runtime is picked up without re-caching. Enabled state is resolved lazily via
 * {@see ModuleRegistry}, so callers must only read this once the database is
 * available (request/boot time, not during provider register()).
 */
class RoleSource
{
    public function __construct(private ModuleRegistry $modules) {}

    /**
     * Every concrete permission name: core catalogue plus module permissions
     * from enabled modules.
     *
     * @return array<int, string>
     */
    public function permissions(): array
    {
        $permissions = array_values((array) config('roles.permissions', []));

        foreach (array_keys($this->modules->permissions()) as $name) {
            $permissions[] = $name;
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Role definitions keyed by their literal role key (module role keys are
     * dotted, e.g. `modules.<slug>.<role>`).
     *
     * @return array<string, array<string, mixed>>
     */
    public function roles(): array
    {
        $roles = (array) config('roles.roles', []);

        foreach ($this->modules->roles() as $key => $role) {
            $roles[$key] = $role['definition'];
        }

        return $roles;
    }

    /**
     * The grant matrix keyed by literal role key.
     *
     * @return array<string, array<int, string>>
     */
    public function matrix(): array
    {
        $matrix = (array) config('roles.matrix', []);

        foreach ($this->modules->roles() as $key => $role) {
            $matrix[$key] = $role['grants'];
        }

        return $matrix;
    }

    /**
     * A single role definition by its literal key, or null if unknown. Use this
     * instead of `config("roles.roles.{$key}")`, which mishandles dotted module
     * role keys.
     *
     * @return array<string, mixed>|null
     */
    public function role(string $key): ?array
    {
        return $this->roles()[$key] ?? null;
    }

    public function isModuleRole(string $key): bool
    {
        return str_starts_with($key, 'modules.');
    }
}
