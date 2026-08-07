<?php

namespace Tests\Support;

use App\Models\Area;
use App\Models\User;

/**
 * Helpers for creating users with the role assignments the feedback and
 * combobox tests need: a global admin, and a moderator scoped to one area.
 */
trait CreatesRoleAssignedUsers
{
    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        return $admin;
    }

    private function moderatorFor(Area $area): User
    {
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        return $moderator;
    }
}
