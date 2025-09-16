<?php

namespace Tests\Feature;

use App\Helpers\VatsimRating;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Group;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PositionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $moderator;

    private User $user;

    private Area $moderatorArea;

    private Area $area2;

    private Position $existingPosition;

    private array $positionData;

    protected function setUp(): void
    {
        parent::setUp();

        // Assume that we've already got group 1 and 2

        // Create Areas
        $this->moderatorArea = Area::factory()->create();
        $this->area2 = Area::factory()->create();

        // Create Users
        $this->admin = User::factory()->create();
        $this->admin->groups()->attach(1, ['area_id' => $this->moderatorArea->id]);

        $this->moderator = User::factory()->create();
        $this->user = User::factory()->create();

        // Assign moderator to area 1
        $this->moderator->groups()->attach(2, ['area_id' => $this->moderatorArea->id]);

        // Create Position in area 1
        $this->existingPosition = Position::factory()->create(['area_id' => $this->moderatorArea->id]);

        $this->positionData = [
            'callsign' => 'TEST_APP',
            'name' => 'Test Approach',
            'frequency' => '123.450',
            'fir' => 'TEST',
            'rating' => VatsimRating::S3->value,
            'area_id' => $this->moderatorArea->id,
        ];
    }

    // region Guest tests
    public function test_guest_is_redirected_from_index()
    {
        $this->get(route('positions.index'))->assertRedirect('login');
    }

    public function test_guest_cannot_store_position()
    {
        $this->post(route('positions.store'), $this->positionData)->assertRedirect('login');
    }

    public function test_guest_cannot_update_position()
    {
        $this->put(route('positions.update', $this->existingPosition), $this->positionData)->assertRedirect('login');
    }

    public function test_guest_cannot_destroy_position()
    {
        $this->delete(route('positions.destroy', $this->existingPosition))->assertRedirect('login');
    }
    // endregion

    // region Basic user tests
    public function test_basic_user_is_forbidden_from_index()
    {
        $this->actingAs($this->user)->get(route('positions.index'))->assertForbidden();
    }

    public function test_basic_user_cannot_store_position()
    {
        $this->actingAs($this->user)->post(route('positions.store'), $this->positionData)->assertForbidden();
    }
    // endregion

    // region Moderator tests
    public function test_moderator_can_view_index()
    {
        $response = $this->actingAs($this->moderator)->get(route('positions.index'));
        $response->assertOk();
        $response->assertViewHas('positions');
        $response->assertViewHas('ratings');
        $response->assertViewHas('areas', function ($areas) {
            return $areas->contains($this->moderatorArea) && ! $areas->contains($this->area2);
        });
    }

    public function test_moderator_can_store_position_in_managed_area()
    {
        $this->markTestIncomplete('sector moderator not implemented');
        $this->actingAs($this->moderator)
            ->post(route('positions.store'), $this->positionData)
            ->assertRedirect(route('positions.index'));

        $this->assertDatabaseHas('positions', ['callsign' => 'TEST_APP']);
    }

    public function test_moderator_is_forbidden_to_store_position_in_unmanaged_area()
    {
        $data = array_merge($this->positionData, ['area_id' => $this->area2->id]);
        $this->actingAs($this->moderator)->post(route('positions.store'), $data)->assertForbidden();
    }

    public function test_moderator_can_update_position_in_managed_area()
    {
        $this->markTestIncomplete('sector moderator not implemented');
        $data = array_merge($this->positionData, ['name' => 'Updated Name']);
        $this->actingAs($this->moderator)
            ->put(route('positions.update', $this->existingPosition), $data)
            ->assertRedirect(route('positions.index'));

        $this->assertDatabaseHas('positions', ['id' => $this->existingPosition->id, 'name' => 'Updated Name']);
    }

    public function test_moderator_is_forbidden_to_update_position_in_unmanaged_area()
    {
        $positionInArea2 = Position::factory()->create(['area_id' => $this->area2->id]);
        $data = array_merge($this->positionData, ['name' => 'Updated Name']);
        $this->actingAs($this->moderator)->put(route('positions.update', $positionInArea2), $data)->assertForbidden();
    }

    public function test_moderator_can_destroy_position_in_managed_area()
    {
        $this->markTestIncomplete('sector moderator not implemented');
        $this->actingAs($this->moderator)
            ->delete(route('positions.destroy', $this->existingPosition))
            ->assertRedirect(route('positions.index'));

        $this->assertDatabaseMissing('positions', ['id' => $this->existingPosition->id]);
    }

    public function test_moderator_is_forbidden_to_destroy_position_in_unmanaged_area()
    {
        $positionInArea2 = Position::factory()->create(['area_id' => $this->area2->id]);
        $this->actingAs($this->moderator)->delete(route('positions.destroy', $positionInArea2))->assertForbidden();
    }
    // endregion

    // region Administration tests
    public function test_admin_can_store_position_in_any_area()
    {
        $data = array_merge($this->positionData, ['area_id' => $this->area2->id, 'callsign' => 'ADM_TEST']);
        $this->actingAs($this->admin)
            ->post(route('positions.store'), $data)
            ->assertRedirect(route('positions.index'));

        $this->assertDatabaseHas('positions', ['callsign' => 'ADM_TEST', 'area_id' => $this->area2->id]);
    }

    public function test_admin_can_update_any_position()
    {
        $positionInArea2 = Position::factory()->create(['area_id' => $this->area2->id]);
        $data = ['name' => 'Admin Update', 'fir' => 'ADES'];
        $this->actingAs($this->admin)
            ->put(route('positions.update', $positionInArea2), array_merge($positionInArea2->toArray(), $data))
            ->assertRedirect(route('positions.index'));

        $this->assertDatabaseHas('positions', ['id' => $positionInArea2->id, 'name' => 'Admin Update']);
    }

    public function test_admin_can_destroy_any_position()
    {
        $positionInArea2 = Position::factory()->create(['area_id' => $this->area2->id]);
        $this->actingAs($this->admin)
            ->delete(route('positions.destroy', $positionInArea2))
            ->assertRedirect(route('positions.index'));

        $this->assertDatabaseMissing('positions', ['id' => $positionInArea2->id]);
    }
    // endregion

    // region Validation tests
    #[DataProvider('validationDataProvider')]
    public function test_store_position_validation($field, $value, $shouldPass)
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

    public function test_store_validates_unique_callsign()
    {
        $this->actingAs($this->admin)
            ->post(route('positions.store'), $this->existingPosition->toArray())
            ->assertSessionHasErrors('callsign');
    }

    public function test_update_ignores_unique_callsign_for_same_model()
    {
        $this->actingAs($this->admin)
            ->put(route('positions.update', $this->existingPosition), array_merge($this->existingPosition->toArray(), ['fir' => 'NEWF']))
            ->assertSessionHasNoErrors('callsign');
    }
    // endregion

    // /region Logging
    public function test_position_creation_is_logged()
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

    public function test_position_update_is_logged()
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

    public function test_position_deletion_is_logged()
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
}
