<?php

namespace Tests\Feature\Admin;

use App\Models\Area;
use App\Models\Group;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\GroupSeeder;
use App\Helpers\VatsimRating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GroupSeeder::class);
    }

    public function test_guest_cannot_view_positions()
    {
        $this->get(route('positions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_cannot_view_positions()
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('positions.index'))
            ->assertForbidden();
    }

    public function test_moderator_can_view_positions()
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->groups()->attach(2, ['area_id' => $area->id]);

        $this->actingAs($moderator)
            ->get(route('positions.index'))
            ->assertOk();
    }

    public function test_admin_can_view_positions()
    {
        $admin = User::factory()->create();
        $area = Area::factory()->create();
        $admin->groups()->attach(1, ['area_id' => $area->id]);

        $this->actingAs($admin)
            ->get(route('positions.index'))
            ->assertOk();
    }

    public function test_admin_can_create_position()
    {
        $admin = User::factory()->create();
        $area = Area::factory()->create();
        $admin->groups()->attach(1, ['area_id' => $area->id]);
        $positionData = Position::factory()->make(['area_id' => $area->id])->toArray();

        $this->actingAs($admin)
            ->post(route('positions.store'), $positionData)
            ->assertRedirect(route('positions.index'));
        $this->assertDatabaseHas('positions', ['callsign' => $positionData['callsign']]);
    }

    public function test_moderator_can_create_position_in_their_area()
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->groups()->attach(2, ['area_id' => $area->id]);
        $positionData = Position::factory()->make(['area_id' => $area->id])->toArray();

        $this->actingAs($moderator)
            ->post(route('positions.store'), $positionData)
            ->assertRedirect(route('positions.index'));
        $this->assertDatabaseHas('positions', ['callsign' => $positionData['callsign']]);
    }

    public function test_moderator_cannot_create_position_outside_their_area()
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->groups()->attach(2, ['area_id' => $area->id]);
        $positionData = Position::factory()->make(['area_id' => $otherArea->id])->toArray();

        $this->actingAs($moderator)
            ->post(route('positions.store'), $positionData)
            ->assertForbidden();
    }

    public function test_user_cannot_create_position()
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $positionData = Position::factory()->make(['area_id' => $area->id])->toArray();

        $this->actingAs($user)
            ->post(route('positions.store'), $positionData)
            ->assertForbidden();
    }

    public function test_admin_can_update_any_position()
    {
        $admin = User::factory()->create();
        $area = Area::factory()->create();
        $admin->groups()->attach(1, ['area_id' => $area->id]);
        $position = Position::factory()->create(['area_id' => $area->id]);
        $updateData = ['callsign' => 'NEW_CALLSIGN', 'area_id' => $area->id, 'name' => 'New Name', 'frequency' => '123.456', 'rating' => VatsimRating::S1->value, 'fir' => 'EKDK'];

        $this->actingAs($admin)
            ->put(route('positions.update', $position), $updateData)
            ->assertRedirect(route('positions.index'));
        $this->assertDatabaseHas('positions', ['id' => $position->id, 'callsign' => 'NEW_CALLSIGN']);
    }

    public function test_moderator_can_update_position_in_their_area()
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->groups()->attach(2, ['area_id' => $area->id]);
        $position = Position::factory()->create(['area_id' => $area->id]);
        $updateData = ['callsign' => 'NEW_CALLSIGN', 'area_id' => $area->id, 'name' => 'New Name', 'frequency' => '123.456', 'rating' => VatsimRating::S1->value, 'fir' => 'EKDK'];

        $this->actingAs($moderator)
            ->put(route('positions.update', $position), $updateData)
            ->assertRedirect(route('positions.index'));
        $this->assertDatabaseHas('positions', ['id' => $position->id, 'callsign' => 'NEW_CALLSIGN']);
    }

    public function test_moderator_cannot_update_position_outside_their_area()
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->groups()->attach(2, ['area_id' => $area->id]);
        $position = Position::factory()->create(['area_id' => $otherArea->id]);
        $updateData = ['callsign' => 'NEW_CALLSIGN', 'area_id' => $otherArea->id, 'name' => 'New Name', 'frequency' => '123.456', 'rating' => VatsimRating::S1->value, 'fir' => 'EKDK'];

        $this->actingAs($moderator)
            ->put(route('positions.update', $position), $updateData)
            ->assertForbidden();
    }

    public function test_user_cannot_update_position()
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $position = Position::factory()->create(['area_id' => $area->id]);
        $updateData = ['callsign' => 'NEW_CALLSIGN', 'area_id' => $area->id, 'name' => 'New Name', 'frequency' => '123.456', 'rating' => VatsimRating::S1->value, 'fir' => 'EKDK'];

        $this->actingAs($user)
            ->put(route('positions.update', $position), $updateData)
            ->assertForbidden();
    }

    public function test_admin_can_delete_any_position()
    {
        $admin = User::factory()->create();
        $area = Area::factory()->create();
        $admin->groups()->attach(1, ['area_id' => $area->id]);
        $position = Position::factory()->create(['area_id' => $area->id]);

        $this->actingAs($admin)
            ->delete(route('positions.destroy', $position))
            ->assertRedirect(route('positions.index'));
        $this->assertDatabaseMissing('positions', ['id' => $position->id]);
    }

    public function test_moderator_can_delete_position_in_their_area()
    {
        $area = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->groups()->attach(2, ['area_id' => $area->id]);
        $position = Position::factory()->create(['area_id' => $area->id]);

        $this->actingAs($moderator)
            ->delete(route('positions.destroy', $position))
            ->assertRedirect(route('positions.index'));
        $this->assertDatabaseMissing('positions', ['id' => $position->id]);
    }

    public function test_moderator_cannot_delete_position_outside_their_area()
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->groups()->attach(2, ['area_id' => $area->id]);
        $position = Position::factory()->create(['area_id' => $otherArea->id]);

        $this->actingAs($moderator)
            ->delete(route('positions.destroy', $position))
            ->assertForbidden();
    }

    public function test_user_cannot_delete_position()
    {
        $user = User::factory()->create();
        $area = Area::factory()->create();
        $position = Position::factory()->create(['area_id' => $area->id]);

        $this->actingAs($user)
            ->delete(route('positions.destroy', $position))
            ->assertForbidden();
    }
}
