<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

    public function test_role_assignment_checkbox_keys_match_controller_expectations(): void
    {
        // Submit with lowercase role key (what the controller expects)
        $key = $this->area->id . '_moderator';

        $response = $this->actingAs($this->admin)
            ->patch(route('user.update', $this->target), [$key => 'on']);

        $response->assertRedirect();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->target->id,
            'role' => 'moderator',
            'area_id' => $this->area->id,
        ]);
    }

    public function test_role_removal_works_when_key_matches(): void
    {
        // Pre-assign moderator role
        $this->target->roleAssignments()->create([
            'role' => 'moderator',
            'area_id' => $this->area->id,
        ]);

        // Submit without that key (unchecked) — should remove the role
        $response = $this->actingAs($this->admin)
            ->patch(route('user.update', $this->target), []);

        $response->assertRedirect();

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'moderator',
            'area_id' => $this->area->id,
        ]);
    }

    public function test_global_row_is_visible_on_user_show_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('user.show', $this->target));

        $response->assertOk();
        $response->assertSee('Global');
    }

    public function test_global_admin_can_assign_director_per_area(): void
    {
        $key = $this->area->id . '_director';

        $response = $this->actingAs($this->admin)
            ->patch(route('user.update', $this->target), [$key => 'on']);

        $response->assertRedirect();

        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->target->id,
            'role' => 'director',
            'area_id' => $this->area->id,
        ]);
    }

    public function test_global_admin_can_revoke_director_per_area(): void
    {
        $this->target->roleAssignments()->create([
            'role' => 'director',
            'area_id' => $this->area->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('user.update', $this->target), []);

        $response->assertRedirect();

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'director',
            'area_id' => $this->area->id,
        ]);
    }

    public function test_admin_role_cannot_be_assigned_via_ui_even_by_global_admin(): void
    {
        $key = $this->area->id . '_admin';

        $response = $this->actingAs($this->admin)
            ->patch(route('user.update', $this->target), [$key => 'on']);

        $response->assertRedirect();
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_role_cannot_be_revoked_via_ui(): void
    {
        $this->target->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $response = $this->actingAs($this->admin)
            ->patch(route('user.update', $this->target), []);

        $response->assertRedirect();
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

        $key = $this->area->id . '_moderator';

        $response = $this->actingAs($director)
            ->patch(route('user.update', $this->target), [$key => 'on']);

        $response->assertRedirect();
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

        $key = $this->area->id . '_director';

        $response = $this->actingAs($director)
            ->patch(route('user.update', $this->target), [$key => 'on']);

        $response->assertRedirect();
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'director',
        ]);
    }

    public function test_global_director_can_assign_director_per_area(): void
    {
        $globalDirector = User::factory()->create();
        $globalDirector->roleAssignments()->create(['role' => 'director', 'area_id' => null]);

        $key = $this->area->id . '_director';

        $response = $this->actingAs($globalDirector)
            ->patch(route('user.update', $this->target), [$key => 'on']);

        $response->assertRedirect();
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

        $response = $this->actingAs($moderator)
            ->patch(route('user.update', $this->target), [
                $this->area->id . '_director' => 'on',
                $this->area->id . '_admin' => 'on',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('role_user', ['user_id' => $this->target->id, 'role' => 'director']);
        $this->assertDatabaseMissing('role_user', ['user_id' => $this->target->id, 'role' => 'admin']);
    }

    public function test_global_admin_can_assign_global_moderator(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('user.update', $this->target), ['global_moderator' => 'on']);

        $response->assertRedirect();
        $this->assertDatabaseHas('role_user', [
            'user_id' => $this->target->id,
            'role' => 'moderator',
            'area_id' => null,
        ]);
    }

    public function test_global_admin_can_revoke_global_moderator(): void
    {
        $this->target->roleAssignments()->create(['role' => 'moderator', 'area_id' => null]);

        $response = $this->actingAs($this->admin)
            ->patch(route('user.update', $this->target), []);

        $response->assertRedirect();
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'moderator',
        ]);
    }

    public function test_global_mentor_key_is_ignored_due_to_area_scope(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('user.update', $this->target), ['global_mentor' => 'on']);

        $response->assertRedirect();
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'mentor',
        ]);
    }

    public function test_global_admin_key_is_ignored(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('user.update', $this->target), ['global_admin' => 'on']);

        $response->assertRedirect();
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'admin',
        ]);
    }

    public function test_global_director_can_assign_global_director(): void
    {
        $globalDirector = User::factory()->create();
        $globalDirector->roleAssignments()->create(['role' => 'director', 'area_id' => null]);

        $response = $this->actingAs($globalDirector)
            ->patch(route('user.update', $this->target), ['global_director' => 'on']);

        $response->assertRedirect();
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

        $response = $this->actingAs($director)
            ->patch(route('user.update', $this->target), ['global_moderator' => 'on']);

        $response->assertRedirect();
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $this->target->id,
            'role' => 'moderator',
        ]);
    }

    public function test_global_row_renders_enabled_checkboxes_for_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('user.show', $this->target));

        $response->assertOk();
        $response->assertSee('name="global_moderator"', false);
        $response->assertSee('name="global_director"', false);
        $response->assertDontSee('name="global_admin"', false);
        $response->assertDontSee('name="global_mentor"', false);
    }
}
