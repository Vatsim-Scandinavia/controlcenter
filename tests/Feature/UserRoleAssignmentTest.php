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
}
