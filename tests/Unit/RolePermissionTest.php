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

    public function test_has_global_role_only_matches_area_less_assignments()
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();

        $user->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $this->assertFalse($user->hasGlobalRole('moderator'));
        $this->assertTrue($user->hasRole('moderator'));
    }

    public function test_has_global_role_matches_global_assignments()
    {
        $user = User::factory()->create();

        $user->roleAssignments()->create(['role' => 'moderator', 'area_id' => null]);

        $this->assertTrue($user->hasGlobalRole('moderator'));
        $this->assertTrue($user->hasGlobalRole(['admin', 'moderator']));
        $this->assertFalse($user->hasGlobalRole('admin'));
    }

    public function test_all_with_permission_scoped_to_area_includes_global_and_area_holders()
    {
        config(['roles.matrix.test-permission' => ['admin', 'moderator']]);

        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();

        $globalAdmin = User::factory()->create();
        $globalAdmin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $areaModerator = User::factory()->create();
        $areaModerator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $otherAreaModerator = User::factory()->create();
        $otherAreaModerator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $otherArea->id]);

        $areaMentor = User::factory()->create();
        $areaMentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);

        $recipients = User::allWithPermission('test-permission', $area);

        $this->assertTrue($recipients->contains($globalAdmin));
        $this->assertTrue($recipients->contains($areaModerator));
        $this->assertFalse($recipients->contains($otherAreaModerator));
        $this->assertFalse($recipients->contains($areaMentor));
    }

    public function test_all_with_permission_without_area_includes_holders_anywhere()
    {
        config(['roles.matrix.test-permission' => ['admin', 'moderator']]);

        $area = Area::factory()->create();

        $globalAdmin = User::factory()->create();
        $globalAdmin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $areaModerator = User::factory()->create();
        $areaModerator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $areaMentor = User::factory()->create();
        $areaMentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);

        $recipients = User::allWithPermission('test-permission');

        $this->assertTrue($recipients->contains($globalAdmin));
        $this->assertTrue($recipients->contains($areaModerator));
        $this->assertFalse($recipients->contains($areaMentor));
    }

    public function test_all_with_permission_matches_has_permission_semantics()
    {
        config(['roles.matrix.test-permission' => ['moderator']]);

        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();

        $areaModerator = User::factory()->create();
        $areaModerator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        foreach ([null, $area, $otherArea] as $queriedArea) {
            $this->assertEquals(
                $areaModerator->hasPermission('test-permission', $queriedArea),
                User::allWithPermission('test-permission', $queriedArea)->contains($areaModerator)
            );
        }
    }

    public function test_all_with_permission_returns_no_users_for_unknown_permission()
    {
        $user = User::factory()->create();
        $user->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->assertTrue(User::allWithPermission('does-not-exist')->isEmpty());
    }

    public function test_all_with_permission_returns_each_user_once_despite_multiple_assignments()
    {
        config(['roles.matrix.test-permission' => ['admin', 'moderator']]);

        $area = Area::factory()->create();

        $user = User::factory()->create();
        $user->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);
        $user->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $this->assertCount(1, User::allWithPermission('test-permission', $area));
    }
}
