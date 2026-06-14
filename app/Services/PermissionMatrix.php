<?php

namespace App\Services;

class PermissionMatrix
{
    /**
     * Compiled regex cache, keyed by pattern.
     *
     * @var array<string, string>
     */
    private array $compiled = [];

    /**
     * Every concrete permission name defined in the catalogue.
     *
     * @return array<int, string>
     */
    public function all(): array
    {
        return array_values(config('roles.permissions', []));
    }

    /**
     * All roles whose grant patterns include the given permission.
     *
     * @return array<int, string>
     */
    public function rolesFor(string $permission): array
    {
        if (! in_array($permission, $this->all(), true)) {
            return [];
        }

        $roles = [];

        foreach (config('roles.matrix', []) as $role => $patterns) {
            if ($this->granted($permission, $patterns)) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    /**
     * Every concrete permission a role holds, after applying wildcards and negation.
     *
     * @return array<int, string>
     */
    public function permissionsFor(string $role): array
    {
        $patterns = config("roles.matrix.{$role}", []);

        return array_values(array_filter(
            $this->all(),
            fn (string $permission): bool => $this->granted($permission, $patterns)
        ));
    }

    /**
     * Decide whether a set of patterns grants a permission. Deny (`!`) always wins.
     *
     * @param  array<int, string>  $patterns
     */
    private function granted(string $permission, array $patterns): bool
    {
        $allowed = false;

        foreach ($patterns as $pattern) {
            if (str_starts_with($pattern, '!')) {
                if ($this->matches(substr($pattern, 1), $permission)) {
                    return false;
                }
            } elseif ($this->matches($pattern, $permission)) {
                $allowed = true;
            }
        }

        return $allowed;
    }

    private function matches(string $pattern, string $permission): bool
    {
        return (bool) preg_match($this->compile($pattern), $permission);
    }

    private function compile(string $pattern): string
    {
        return $this->compiled[$pattern] ??= '/^' . implode('\.', array_map(
            fn (string $segment): string => match ($segment) {
                '**' => '.+',
                '*' => '[^.]+',
                default => preg_quote($segment, '/'),
            },
            explode('.', $pattern)
        )) . '$/';
    }
}
