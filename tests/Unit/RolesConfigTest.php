<?php

namespace Tests\Unit;

use App\Services\PermissionMatrix;
use Tests\TestCase;

class RolesConfigTest extends TestCase
{
    public function test_every_matrix_role_is_a_defined_role(): void
    {
        $definedRoles = array_keys(config('roles.roles'));

        foreach (array_keys(config('roles.matrix')) as $role) {
            $this->assertContains($role, $definedRoles, "Matrix references undefined role {$role}");
        }
    }

    public function test_every_role_grants_at_least_one_permission(): void
    {
        $matrix = new PermissionMatrix;

        foreach (array_keys(config('roles.matrix')) as $role) {
            $this->assertNotEmpty($matrix->permissionsFor($role), "Role '{$role}' grants no permissions.");
        }
    }

    public function test_permission_catalogue_is_unique_and_non_empty(): void
    {
        $permissions = config('roles.permissions');

        $this->assertNotEmpty($permissions);
        $this->assertSame(array_unique($permissions), $permissions, 'The permission catalogue contains duplicates.');
    }

    public function test_all_returns_the_catalogue(): void
    {
        $this->assertSame(array_values(config('roles.permissions')), (new PermissionMatrix)->all());
    }

    public function test_no_orphan_permissions(): void
    {
        $matrix = new PermissionMatrix;

        foreach ($matrix->all() as $permission) {
            $this->assertNotEmpty($matrix->rolesFor($permission), "Permission '{$permission}' is granted to no role.");
        }
    }
}
