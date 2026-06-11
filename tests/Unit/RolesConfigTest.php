<?php

namespace Tests\Unit;

use Tests\TestCase;

class RolesConfigTest extends TestCase
{
    public function test_every_matrix_role_is_a_defined_role(): void
    {
        $definedRoles = array_keys(config('roles.roles'));

        foreach (config('roles.matrix') as $permission => $roles) {
            foreach ($roles as $role) {
                $this->assertContains($role, $definedRoles, "Permission {$permission} references undefined role {$role}");
            }
        }
    }

    public function test_permissions_for_migrated_hasrole_checks_exist(): void
    {
        $matrix = config('roles.matrix');

        $expected = [
            'manage-tasks', 'suggested-task-recipient',
            'manage-files', 'upload-files',
            'manage-bookings', 'use-sweatbook', 'manage-sweatbook',
            'manage-notification-templates',
            'view-training-reports', 'create-training-reports', 'update-training-reports', 'delete-training-reports',
            'use-report-one-time-link', 'view-hidden-training-attachments',
            'manage-examinations', 'create-examinations',
            'view-mentor-dashboard', 'mentor-trainings',
            'receive-training-notifications',
            'manage-settings', 'manage-votes', 'view-activity-log',
        ];

        foreach ($expected as $permission) {
            $this->assertArrayHasKey($permission, $matrix);
        }
    }

    public function test_every_matrix_permission_has_at_least_one_role(): void
    {
        foreach (config('roles.matrix') as $permission => $roles) {
            $this->assertNotEmpty($roles, "Permission '{$permission}' has no roles assigned.");
        }
    }
}
