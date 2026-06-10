<?php

namespace Tests\Feature;

use App\Helpers\VatsimRating;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Position;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PositionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $permittedUser;

    private User $user;

    private Area $permittedArea;

    private Area $area2;

    private Position $existingPosition;

    private Position $existingPositionOther;

    private array $positionData;

    protected function setUp(): void
    {
        parent::setUp();

        // Assume that we've already got group 1 and 2

        // Create Areas
        $this->permittedArea = Area::factory()->create();
        $this->area2 = Area::factory()->create();

        // Create Users
        $this->admin = User::factory()->create();
        $this->admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->permittedUser = User::factory()->create();
        $this->user = User::factory()->create();

        // Assign navigational editor to area 1
        $this->permittedUser->roleAssignments()->create(['role' => 'nav-editor', 'area_id' => $this->permittedArea->id]);

        // Create Position in area 1
        $this->existingPosition = Position::factory()->create(['area_id' => $this->permittedArea->id]);

        // Create Position in area 2
        $this->existingPositionOther = Position::factory()->create(['area_id' => $this->area2->id]);

        $this->positionData = [
            'callsign' => 'TEST_APP',
            'name' => 'Test Approach',
            'frequency' => '123.450',
            'fir' => 'TEST',
            'rating' => VatsimRating::S3->value,
            'area_id' => $this->permittedArea->id,
        ];
    }

    // region Guest tests
    #[Test]
    public function guest_is_redirected_from_index()
    {
        $this->get(route('positions.index'))->assertRedirect('login');
    }

    #[Test]
    public function guest_cannot_store_position()
    {
        $this->post(route('positions.store'), $this->positionData)->assertRedirect('login');
    }

    #[Test]
    public function guest_cannot_update_position()
    {
        $this->put(route('positions.update', $this->existingPosition), $this->positionData)->assertRedirect('login');
    }

    #[Test]
    public function guest_cannot_destroy_position()
    {
        $this->delete(route('positions.destroy', $this->existingPosition))->assertRedirect('login');
    }
    // endregion

    // region Basic user tests
    #[Test]
    public function basic_user_is_forbidden_from_index()
    {
        $this->actingAs($this->user)->get(route('positions.index'))->assertForbidden();
    }

    #[Test]
    public function basic_user_cannot_store_position()
    {
        $this->actingAs($this->user)->post(route('positions.store'), $this->positionData)->assertForbidden();
    }
    // endregion

    // region Permitted user tests
    #[Test]
    public function permitted_user_can_view_index()
    {
        $response = $this->actingAs($this->permittedUser)->get(route('positions.index'));
        $response->assertOk();
        $response->assertViewHas('positions');
        $response->assertViewHas('ratings');
        $response->assertViewHas('areas', function ($areas) {
            return $areas->contains($this->permittedArea) && ! $areas->contains($this->area2);
        });
    }

    #[Test]
    public function permitted_user_can_store_position_in_managed_area()
    {
        $this->actingAs($this->permittedUser)
            ->post(route('positions.store'), $this->positionData)
            ->assertRedirect(route('positions.index'));

        $this->assertDatabaseHas('positions', ['callsign' => 'TEST_APP']);
    }

    #[Test]
    public function permitted_user_is_forbidden_to_store_position_in_unmanaged_area()
    {
        $data = array_merge($this->positionData, ['area_id' => $this->area2->id]);
        $this->actingAs($this->permittedUser)->post(route('positions.store'), $data)->assertForbidden();
    }

    #[Test]
    public function permitted_user_can_update_position_in_managed_area()
    {
        $data = array_merge($this->positionData, ['name' => 'Updated Name']);
        $this->actingAs($this->permittedUser)
            ->put(route('positions.update', $this->existingPosition), $data)
            ->assertRedirect(route('positions.index'));

        $this->assertDatabaseHas('positions', ['id' => $this->existingPosition->id, 'name' => 'Updated Name']);
    }

    #[Test]
    public function permitted_user_is_forbidden_to_update_position_in_unmanaged_area()
    {
        $positionInArea2 = Position::factory()->create(['area_id' => $this->area2->id]);
        $data = array_merge($this->positionData, ['name' => 'Updated Name']);
        $this->actingAs($this->permittedUser)->put(route('positions.update', $positionInArea2), $data)->assertForbidden();
    }

    #[Test]
    public function permitted_user_is_forbidden_to_move_position_to_another_area()
    {
        $data = array_merge($this->positionData, ['area_id' => $this->area2->id]);
        $this->actingAs($this->permittedUser)
            ->put(route('positions.update', $this->existingPosition), $data)
            ->assertForbidden();

        $this->assertDatabaseHas('positions', [
            'id' => $this->existingPosition->id,
            'area_id' => $this->permittedArea->id,
        ]);
    }

    #[Test]
    public function permitted_user_can_destroy_position_in_managed_area()
    {
        $this->actingAs($this->permittedUser)
            ->delete(route('positions.destroy', $this->existingPosition))
            ->assertRedirect(route('positions.index'));

        $this->assertDatabaseMissing('positions', ['id' => $this->existingPosition->id]);
    }

    #[Test]
    public function permitted_user_can_view_required_endorsement_in_modal()
    {
        $rating = Rating::factory()->create(['name' => 'Test Endorsement']);
        Position::factory()->create([
            'area_id' => $this->permittedArea->id,
            'required_facility_rating_id' => $rating->id,
        ]);

        $this->actingAs($this->permittedUser)
            ->get(route('positions.index'))
            ->assertOk()
            ->assertSee($rating->name);
    }

    #[Test]
    public function permitted_user_is_forbidden_to_destroy_position_in_unmanaged_area()
    {
        $positionInArea2 = Position::factory()->create(['area_id' => $this->area2->id]);
        $this->actingAs($this->permittedUser)->delete(route('positions.destroy', $positionInArea2))->assertForbidden();
    }
    // endregion

    // region Administration tests
    #[Test]
    public function admin_can_store_position_in_any_area()
    {
        $data = array_merge($this->positionData, ['area_id' => $this->area2->id, 'callsign' => 'ADM_TEST']);
        $this->actingAs($this->admin)
            ->post(route('positions.store'), $data)
            ->assertRedirect(route('positions.index.area', $this->area2->id));

        $this->assertDatabaseHas('positions', ['callsign' => 'ADM_TEST', 'area_id' => $this->area2->id]);
    }

    #[Test]
    public function admin_can_update_any_position()
    {
        $positionInArea2 = Position::factory()->create(['area_id' => $this->area2->id]);
        $data = ['name' => 'Admin Update', 'fir' => 'ADES'];
        $this->actingAs($this->admin)
            ->put(route('positions.update', $positionInArea2), array_merge($positionInArea2->toArray(), $data))
            ->assertRedirect(route('positions.index.area', $this->area2->id));

        $this->assertDatabaseHas('positions', ['id' => $positionInArea2->id, 'name' => 'Admin Update']);
    }

    #[Test]
    public function admin_can_destroy_any_position()
    {
        $positionInArea2 = Position::factory()->create(['area_id' => $this->area2->id]);
        $this->actingAs($this->admin)
            ->delete(route('positions.destroy', $positionInArea2))
            ->assertRedirect(route('positions.index.area', $this->area2->id));

        $this->assertDatabaseMissing('positions', ['id' => $positionInArea2->id]);
    }
    // endregion

    // region Validation tests
    #[Test]
    #[DataProvider('validationDataProvider')]
    public function store_position_validation($field, $value, $shouldPass)
    {
        $data = $this->positionData;
        if ($value === null) {
            unset($data[$field]);
        } else {
            $data[$field] = $value;
        }

        $response = $this->actingAs($this->admin)->post(route('positions.store'), $data);

        if ($shouldPass) {
            $response->assertSessionHasNoErrors();
        } else {
            $response->assertSessionHasErrors($field);
        }
    }

    public static function validationDataProvider(): array
    {
        return [
            'callsign: required' => ['callsign', null, false],
            'name: required' => ['name', null, false],
            'frequency: required' => ['frequency', null, false],
            'frequency: numeric' => ['frequency', 'abc', false],
            'frequency: min' => ['frequency', '117.974', false],
            'frequency: max' => ['frequency', '137.001', false],
            'frequency: decimal' => ['frequency', '123.4567', false],
            'fir: required' => ['fir', null, false],
            'fir: size' => ['fir', 'ABC', false],
            'fir: uppercase' => ['fir', 'test', false],
            'rating: required' => ['rating', null, false],
            'rating: invalid' => ['rating', 999, false],
            'area_id: required' => ['area_id', null, false],
        ];
    }

    #[Test]
    public function store_validates_unique_callsign()
    {
        $this->actingAs($this->admin)
            ->post(route('positions.store'), $this->existingPosition->toArray())
            ->assertSessionHasErrors('callsign');
    }

    #[Test]
    public function update_ignores_unique_callsign_for_same_model()
    {
        $this->actingAs($this->admin)
            ->put(route('positions.update', $this->existingPosition), array_merge($this->existingPosition->toArray(), ['fir' => 'NEWF']))
            ->assertSessionHasNoErrors('callsign');
    }
    // endregion

    // /region Logging
    #[Test]
    public function position_creation_is_logged()
    {
        $this->markTestIncomplete('activity logging not implemented yet');
        ActivityLog::query()->delete();
        $this->actingAs($this->admin)->post(route('positions.store'), $this->positionData);
        $this->assertDatabaseCount('activity_logs', 1);
        $log = ActivityLog::first();
        $this->assertEquals('SECTOR', $log->category);
        $this->assertStringContainsString('Position created', $log->message);
        $this->assertStringContainsString($this->positionData['callsign'], $log->message);
    }

    #[Test]
    public function position_update_is_logged()
    {
        $this->markTestIncomplete('activity logging not implemented yet');
        ActivityLog::query()->delete();
        $data = array_merge($this->existingPosition->toArray(), ['name' => 'New Name', 'fir' => 'NEWF']);
        $this->actingAs($this->admin)->put(route('positions.update', $this->existingPosition), $data);
        $this->assertDatabaseCount('activity_logs', 1);
        $log = ActivityLog::first();
        $this->assertEquals('SECTOR', $log->category);
        $this->assertStringContainsString('Position updated', $log->message);
        $this->assertStringContainsString("Name: {$this->existingPosition->name} → New Name", $log->message);
    }

    #[Test]
    public function position_deletion_is_logged()
    {
        $this->markTestIncomplete('activity logging not implemented yet');
        ActivityLog::query()->delete();
        $this->actingAs($this->admin)->delete(route('positions.destroy', $this->existingPosition));
        $this->assertDatabaseCount('activity_logs', 1);
        $log = ActivityLog::first();
        $this->assertEquals('SECTOR', $log->category);
        $this->assertStringContainsString('Position deleted', $log->message);
        $this->assertStringContainsString($this->existingPosition->callsign, $log->message);
    }
    // /endregion

    // region Filter tests
    #[Test]
    public function index_shows_all_positions_by_default()
    {
        $response = $this->actingAs($this->admin)->get(route('positions.index'));

        $response->assertOk();
        $response->assertSee($this->existingPosition->callsign);
        $response->assertSee($this->existingPositionOther->callsign);
        $response->assertViewHas('currentArea', null);
    }

    #[Test]
    public function index_filters_by_area()
    {
        $response = $this->actingAs($this->admin)->get(route('positions.index.area', $this->area2->id));

        $response->assertOk();
        $response->assertSee($this->existingPositionOther->callsign);
        $response->assertDontSee($this->existingPosition->callsign);
        $response->assertViewHas('currentArea', function ($area) {
            return $area->id === $this->area2->id;
        });
    }

    #[Test]
    public function index_defaults_to_all_positions_if_requested_area_invalid()
    {
        $response = $this->actingAs($this->admin)->get(route('positions.index.area', 999));

        $response->assertOk();
        // Should default to all positions
        $response->assertSee($this->existingPosition->callsign);
        $response->assertSee($this->existingPositionOther->callsign);
        $response->assertViewHas('currentArea', null);
    }

    #[Test]
    public function update_redirects_to_positions_area()
    {
        $data = array_merge($this->existingPositionOther->toArray(), ['name' => 'Updated POS2', 'fir' => 'TEST']);
        $response = $this->actingAs($this->admin)->put(route('positions.update', $this->existingPositionOther), $data);

        $response->assertRedirect(route('positions.index.area', $this->area2->id));
    }

    #[Test]
    public function moderator_of_one_area_cannot_see_positions_of_another_area()
    {
        $otherModerator = User::factory()->create();
        $otherModerator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $this->area2->id]); // Moderator of Area 2

        // Case 1: Explicitly requesting Area 1 (permittedArea) should be forbidden
        $response = $this->actingAs($otherModerator)->get(route('positions.index.area', $this->permittedArea->id));
        $response->assertForbidden();

        // Case 2: General Index should only show Area 2 positions
        $response = $this->actingAs($otherModerator)->get(route('positions.index'));
        $response->assertOk();
        $response->assertSee($this->existingPositionOther->callsign);
        $response->assertDontSee($this->existingPosition->callsign);
    }
    // endregion
}
