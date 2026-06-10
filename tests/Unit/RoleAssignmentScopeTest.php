<?php

namespace Tests\Unit;

use App\Models\Area;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RoleAssignmentScopeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->area = Area::factory()->create();
    }

    #[Test]
    public function area_role_requires_an_area()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Role 'nav-editor' requires an area assignment.");

        $this->user->roleAssignments()->create(['role' => 'nav-editor', 'area_id' => null]);
    }

    #[Test]
    public function area_role_can_be_assigned_to_a_specific_area()
    {
        $this->user->roleAssignments()->create(['role' => 'nav-editor', 'area_id' => $this->area->id]);

        $this->assertDatabaseHas('role_user', ['role' => 'nav-editor', 'area_id' => $this->area->id]);
    }

    #[Test]
    public function mentor_role_requires_an_area()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Role 'mentor' requires an area assignment.");

        $this->user->roleAssignments()->create(['role' => 'mentor', 'area_id' => null]);
    }

    #[Test]
    public function buddy_role_requires_an_area()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Role 'buddy' requires an area assignment.");

        $this->user->roleAssignments()->create(['role' => 'buddy', 'area_id' => null]);
    }

    #[Test]
    public function both_scope_role_can_be_assigned_globally()
    {
        $this->user->roleAssignments()->create(['role' => 'moderator', 'area_id' => null]);

        $this->assertDatabaseHas('role_user', ['role' => 'moderator', 'area_id' => null]);
    }

    #[Test]
    public function admin_can_be_assigned_globally()
    {
        $this->user->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->assertDatabaseHas('role_user', ['role' => 'admin', 'area_id' => null]);
    }

    #[Test]
    public function admin_cannot_be_assigned_to_an_area()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Role 'admin' is global and cannot be assigned to an area.");

        $this->user->roleAssignments()->create(['role' => 'admin', 'area_id' => $this->area->id]);
    }

    #[Test]
    public function director_can_be_assigned_to_a_specific_area()
    {
        $this->user->roleAssignments()->create(['role' => 'director', 'area_id' => $this->area->id]);

        $this->assertDatabaseHas('role_user', ['role' => 'director', 'area_id' => $this->area->id]);
    }

    #[Test]
    public function director_can_be_assigned_globally()
    {
        $this->user->roleAssignments()->create(['role' => 'director', 'area_id' => null]);

        $this->assertDatabaseHas('role_user', ['role' => 'director', 'area_id' => null]);
    }

    #[Test]
    public function global_role_cannot_be_assigned_to_an_area()
    {
        config(['roles.roles.system.scope' => 'global']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Role 'system' is global and cannot be assigned to an area.");

        $this->user->roleAssignments()->create(['role' => 'system', 'area_id' => $this->area->id]);
    }

    #[Test]
    public function updating_global_role_to_an_area_throws()
    {
        config(['roles.roles.system.scope' => 'global']);

        $assignment = RoleAssignment::create([
            'user_id' => $this->user->id,
            'role' => 'system',
            'area_id' => null,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Role 'system' is global and cannot be assigned to an area.");

        $assignment->update(['area_id' => $this->area->id]);
    }

    #[Test]
    public function unknown_role_throws_on_create()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Role 'fake-role' is not a recognised role.");

        $this->user->roleAssignments()->create(['role' => 'fake-role', 'area_id' => $this->area->id]);
    }

    #[Test]
    public function updating_area_role_to_null_area_throws()
    {
        $assignment = RoleAssignment::create([
            'user_id' => $this->user->id,
            'role' => 'nav-editor',
            'area_id' => $this->area->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Role 'nav-editor' requires an area assignment.");

        $assignment->update(['area_id' => null]);
    }
}
