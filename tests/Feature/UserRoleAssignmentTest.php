<?php

namespace Tests\Feature;

use App\Facades\DivisionApi;
use App\Livewire\UserRoles;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Behavioural coverage for role assignment now that the Bootstrap role matrix
 * (form + user.update endpoint) has been replaced by the UserRoles Livewire
 * component. Each test drives the component the same way the profile page does.
 */
class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $target;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->area = Area::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->target = User::factory()->create();
    }

    public function test_user_show_page_renders_the_user_roles_component(): void
    {
        $this->actingAs($this->admin)
            ->get(route('user.show', $this->target))
            ->assertOk()
            ->assertSeeLivewire(UserRoles::class);
    }

    public function test_global_admin_can_assign_moderator_per_area(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserRoles::class, ['user' => $this->target])
            ->call('openAddModal')
            ->set('selectedRole', 'moderator')
            ->set('selectedAreaIds', [$this->area->id])
            ->call('grant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->target->id,
            'role' => 'moderator',
            'area_id' => $this->area->id,
        ]);
    }

    public function test_revoking_a_role_via_the_component_is_logged(): void
    {
        $this->target->roleAssignments()->create([
            'role' => 'moderator',
            'area_id' => $this->area->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(UserRoles::class, ['user' => $this->target])
            ->call('confirmRemoval', 'moderator', $this->area->id)
            ->call('remove');

        $log = ActivityLog::where('subject_type', User::class)
            ->where('subject_id', $this->target->id)
            ->where('log_name', 'role')
            ->where('event', 'deleted')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Role revoked', $log->description);
        $this->assertSame('moderator', $log->properties['role']);
        $this->assertSame($this->area->name, $log->properties['area']);
        $this->assertSame($this->admin->id, $log->causer_id);
    }

    public function test_admin_role_cannot_be_assigned_via_the_component_even_by_global_admin(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserRoles::class, ['user' => $this->target])
            ->set('selectedRole', 'admin')
            ->set('selectedGlobal', true)
            ->call('grant');

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_role_cannot_be_revoked_via_the_component(): void
    {
        $this->target->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        Livewire::actingAs($this->admin)
            ->test(UserRoles::class, ['user' => $this->target])
            ->call('confirmRemoval', 'admin', null)
            ->call('remove')
            ->assertForbidden();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->target->id,
            'role' => 'admin',
            'area_id' => null,
        ]);
    }

    public function test_area_director_can_assign_moderator_in_their_area(): void
    {
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $this->area->id]);

        Livewire::actingAs($director)
            ->test(UserRoles::class, ['user' => $this->target])
            ->call('openAddModal')
            ->set('selectedRole', 'moderator')
            ->set('selectedAreaIds', [$this->area->id])
            ->call('grant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->target->id,
            'role' => 'moderator',
            'area_id' => $this->area->id,
        ]);
    }

    public function test_area_director_cannot_assign_director(): void
    {
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $this->area->id]);

        Livewire::actingAs($director)
            ->test(UserRoles::class, ['user' => $this->target])
            ->set('selectedRole', 'director')
            ->set('selectedAreaIds', [$this->area->id])
            ->call('grant');

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'director',
        ]);
    }

    public function test_global_director_can_assign_director_per_area(): void
    {
        $globalDirector = User::factory()->create();
        $globalDirector->roleAssignments()->create(['role' => 'director', 'area_id' => null]);

        Livewire::actingAs($globalDirector)
            ->test(UserRoles::class, ['user' => $this->target])
            ->call('openAddModal')
            ->set('selectedRole', 'director')
            ->set('selectedAreaIds', [$this->area->id])
            ->call('grant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->target->id,
            'role' => 'director',
            'area_id' => $this->area->id,
        ]);
    }

    public function test_moderator_cannot_assign_director_or_admin(): void
    {
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $this->area->id]);

        $component = Livewire::actingAs($moderator)
            ->test(UserRoles::class, ['user' => $this->target]);

        $component->set('selectedRole', 'director')
            ->set('selectedAreaIds', [$this->area->id])
            ->call('grant');

        $component->set('selectedRole', 'admin')
            ->set('selectedAreaIds', [])
            ->set('selectedGlobal', true)
            ->call('grant');

        $this->assertDatabaseMissing('role_user', ['user_id' => $this->target->id, 'role' => 'director']);
        $this->assertDatabaseMissing('role_user', ['user_id' => $this->target->id, 'role' => 'admin']);
    }

    public function test_global_admin_can_assign_global_moderator(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserRoles::class, ['user' => $this->target])
            ->call('openAddModal')
            ->set('selectedRole', 'moderator')
            ->set('selectedGlobal', true)
            ->call('grant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->target->id,
            'role' => 'moderator',
            'area_id' => null,
        ]);
    }

    public function test_global_admin_can_revoke_global_moderator(): void
    {
        $this->target->roleAssignments()->create(['role' => 'moderator', 'area_id' => null]);

        Livewire::actingAs($this->admin)
            ->test(UserRoles::class, ['user' => $this->target])
            ->call('confirmRemoval', 'moderator', null)
            ->call('remove');

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'moderator',
            'area_id' => null,
        ]);
    }

    public function test_mentor_cannot_be_assigned_globally_due_to_area_scope(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserRoles::class, ['user' => $this->target])
            ->call('openAddModal')
            ->set('selectedRole', 'mentor')
            ->call('grant');

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'mentor',
        ]);
    }

    public function test_global_director_can_assign_global_director(): void
    {
        $globalDirector = User::factory()->create();
        $globalDirector->roleAssignments()->create(['role' => 'director', 'area_id' => null]);

        Livewire::actingAs($globalDirector)
            ->test(UserRoles::class, ['user' => $this->target])
            ->call('openAddModal')
            ->set('selectedRole', 'director')
            ->set('selectedGlobal', true)
            ->call('grant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->target->id,
            'role' => 'director',
            'area_id' => null,
        ]);
    }

    public function test_area_director_cannot_assign_global_roles(): void
    {
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $this->area->id]);

        Livewire::actingAs($director)
            ->test(UserRoles::class, ['user' => $this->target])
            ->set('selectedRole', 'moderator')
            ->set('selectedAreaIds', [])
            ->set('selectedGlobal', true)
            ->call('grant');

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'moderator',
            'area_id' => null,
        ]);
    }

    public function test_area_training_staff_can_assign_mentor_in_their_area(): void
    {
        DivisionApi::shouldReceive('assignMentor')->once()->andReturn(false);

        $trainingStaff = User::factory()->create();
        $trainingStaff->roleAssignments()->create(['role' => 'training-staff', 'area_id' => $this->area->id]);

        Livewire::actingAs($trainingStaff)
            ->test(UserRoles::class, ['user' => $this->target])
            ->call('openAddModal')
            ->set('selectedRole', 'mentor')
            ->set('selectedAreaIds', [$this->area->id])
            ->call('grant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->target->id,
            'role' => 'mentor',
            'area_id' => $this->area->id,
        ]);
    }

    public function test_area_training_staff_cannot_assign_moderator_or_director(): void
    {
        $trainingStaff = User::factory()->create();
        $trainingStaff->roleAssignments()->create(['role' => 'training-staff', 'area_id' => $this->area->id]);

        $component = Livewire::actingAs($trainingStaff)->test(UserRoles::class, ['user' => $this->target]);

        $component->set('selectedRole', 'moderator')->set('selectedAreaIds', [$this->area->id])->call('grant');
        $component->set('selectedRole', 'director')->set('selectedAreaIds', [$this->area->id])->call('grant');

        $this->assertDatabaseMissing('role_user', ['user_id' => $this->target->id, 'role' => 'moderator']);
        $this->assertDatabaseMissing('role_user', ['user_id' => $this->target->id, 'role' => 'director']);
    }
}
