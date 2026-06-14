<?php

namespace Tests\Unit;

use App\Services\PermissionMatrix;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    private function matrix(): PermissionMatrix
    {
        config([
            'roles.permissions' => [
                'training.view',
                'training.delete',
                'training.reports.view',
                'training.reports.one-time-link',
                'system.health.view',
                'system.settings.manage',
            ],
            'roles.matrix' => [
                'all' => ['**'],
                'training_top' => ['training.*'],
                'training_deep' => ['training.**'],
                'minus' => ['training.**', '!training.delete'],
                'minus_namespace' => ['**', '!system.**'],
            ],
        ]);

        return new PermissionMatrix;
    }

    public function test_double_star_matches_one_or_more_segments(): void
    {
        $perms = $this->matrix()->permissionsFor('training_deep');

        $this->assertContains('training.view', $perms);
        $this->assertContains('training.reports.view', $perms);
        $this->assertNotContains('system.health.view', $perms);
    }

    public function test_single_star_matches_exactly_one_segment(): void
    {
        $perms = $this->matrix()->permissionsFor('training_top');

        $this->assertContains('training.view', $perms);
        $this->assertContains('training.delete', $perms);
        $this->assertNotContains('training.reports.view', $perms);
    }

    public function test_negation_denies_a_matched_permission(): void
    {
        $perms = $this->matrix()->permissionsFor('minus');

        $this->assertContains('training.view', $perms);
        $this->assertNotContains('training.delete', $perms);
    }

    public function test_negation_with_namespace_wildcard(): void
    {
        $perms = $this->matrix()->permissionsFor('minus_namespace');

        $this->assertContains('training.view', $perms);
        $this->assertNotContains('system.health.view', $perms);
        $this->assertNotContains('system.settings.manage', $perms);
    }

    public function test_roles_for_returns_every_role_that_grants_permission(): void
    {
        $roles = $this->matrix()->rolesFor('training.view');

        sort($roles);
        $this->assertSame(['all', 'minus', 'minus_namespace', 'training_deep', 'training_top'], $roles);
    }

    public function test_roles_for_excludes_role_when_negated(): void
    {
        $this->assertNotContains('minus', $this->matrix()->rolesFor('training.delete'));
        $this->assertNotContains('minus_namespace', $this->matrix()->rolesFor('system.health.view'));
    }

    public function test_unknown_permission_yields_no_roles(): void
    {
        $this->assertSame([], $this->matrix()->rolesFor('does.not.exist'));
    }

    public function test_all_returns_the_catalogue(): void
    {
        $this->assertContains('training.view', $this->matrix()->all());
        $this->assertContains('system.settings.manage', $this->matrix()->all());
    }

    public function test_container_resolves_a_shared_instance(): void
    {
        $this->assertSame(
            app(PermissionMatrix::class),
            app(PermissionMatrix::class)
        );
    }
}
