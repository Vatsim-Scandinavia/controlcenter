<?php

namespace Tests\Unit;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'roles.matrix' => [
                'view-training' => ['admin', 'moderator', 'mentor', 'buddy'],
                'create-training' => ['admin', 'moderator', 'mentor'],
                'update-training' => ['admin', 'moderator'],
                'delete-training' => ['admin'],
                'mentor-specific' => ['mentor'],
                'manage-area' => ['admin'],
            ],
            'roles.roles' => [
                'admin' => ['name' => 'Admin', 'description' => 'A', 'scope' => 'global'],
                'moderator' => ['name' => 'Mod', 'description' => 'M', 'scope' => 'both'],
                'mentor' => ['name' => 'Mentor', 'description' => 'M', 'scope' => 'area'],
                'buddy' => ['name' => 'Buddy', 'description' => 'B', 'scope' => 'area'],
            ],
        ]);
    }

    public function test_user_has_role_globally()
    {
        $user = User::factory()->create();

        $user->roleAssignments()->create([
            'role' => 'admin',
            'area_id' => null, // global
        ]);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('admin', Area::factory()->create())); // Global role applies to specific area too
        $this->assertFalse($user->hasRole('moderator'));
    }

    public function test_user_has_role_in_specific_area()
    {
        $user = User::factory()->create();
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();

        $user->roleAssignments()->create([
            'role' => 'mentor',
            'area_id' => $area1->id,
        ]);

        $this->assertTrue($user->hasRole('mentor', $area1));
        $this->assertFalse($user->hasRole('mentor', $area2));
    }

    public function test_user_has_granular_permission()
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();

        $user->roleAssignments()->create([
            'role' => 'moderator',
            'area_id' => $area->id,
        ]);

        // Moderator can update training
        $this->assertTrue($user->hasPermission('update-training', $area));

        // Moderator cannot delete training
        $this->assertFalse($user->hasPermission('delete-training', $area));

        // Moderator cannot do mentor-specific actions
        $this->assertFalse($user->hasPermission('mentor-specific', $area));
    }

    public function test_user_has_multiple_roles()
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();

        $user->roleAssignments()->create([
            'role' => 'moderator',
            'area_id' => $area->id,
        ]);

        $this->assertTrue($user->hasRole(['admin', 'moderator'], $area));
        $this->assertFalse($user->hasRole(['admin', 'mentor'], $area));
    }
}
