<?php

namespace Tests\Feature\Actions;

use App\Actions\RevokeRole;
use App\Facades\DivisionApi;
use App\Models\Area;
use App\Models\Training;
use App\Models\User;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevokeRoleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        return $admin;
    }

    #[Test]
    public function it_deletes_the_assignment(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $area = Area::factory()->create();
        $target->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $error = (new RevokeRole)($actor, $target, 'moderator', $area);

        $this->assertNull($error);
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $target->id, 'role' => 'moderator', 'area_id' => $area->id,
        ]);
    }

    #[Test]
    public function it_detaches_trainings_in_area_when_no_longer_mentor_anywhere(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $area = Area::factory()->create();
        $target->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);
        $training = Training::factory()->create(['area_id' => $area->id]);
        $target->teaches()->attach($training->id, ['expire_at' => now()->addMonths(12)]);

        (new RevokeRole)($actor, $target, 'mentor', $area);

        $this->assertDatabaseMissing('training_mentor', [
            'user_id' => $target->id, 'training_id' => $training->id,
        ]);
    }

    #[Test]
    public function it_keeps_trainings_when_still_mentor_in_another_area(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $areaA = Area::factory()->create();
        $areaB = Area::factory()->create();
        $target->roleAssignments()->create(['role' => 'mentor', 'area_id' => $areaA->id]);
        $target->roleAssignments()->create(['role' => 'mentor', 'area_id' => $areaB->id]);
        $training = Training::factory()->create(['area_id' => $areaA->id]);
        $target->teaches()->attach($training->id, ['expire_at' => now()->addMonths(12)]);

        (new RevokeRole)($actor, $target, 'mentor', $areaA);

        $this->assertDatabaseHas('training_mentor', [
            'user_id' => $target->id, 'training_id' => $training->id,
        ]);
    }

    #[Test]
    public function it_detaches_trainings_when_the_roleassignments_relation_was_cached_before_revocation(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $area = Area::factory()->create();
        $target->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);

        // Force the roleAssignments relation to load and cache on the model
        // instance we pass into the action, simulating a realistic caller
        // that already accessed the relation (e.g. via hasRole()) beforehand.
        // Without RevokeRole's unsetRelation('roleAssignments') refresh, this
        // stale cached collection would still contain the deleted assignment
        // and hasRole('mentor') would wrongly report true.
        $target->load('roleAssignments');

        $training = Training::factory()->create(['area_id' => $area->id]);
        $target->teaches()->attach($training->id, ['expire_at' => now()->addMonths(12)]);

        (new RevokeRole)($actor, $target, 'mentor', $area);

        $this->assertDatabaseMissing('training_mentor', [
            'user_id' => $target->id, 'training_id' => $training->id,
        ]);
    }

    #[Test]
    public function it_blocks_removal_when_division_api_fails(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $area = Area::factory()->create();
        $target->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);

        $failed = new ClientResponse(new GuzzleResponse(422, [], json_encode(['message' => 'boom'])));
        DivisionApi::shouldReceive('removeMentor')->once()->andReturn($failed);
        DivisionApi::shouldReceive('getName')->andReturn('VATEUD');

        $error = (new RevokeRole)($actor, $target, 'mentor', $area);

        $this->assertSame('Request failed due to error in VATEUD API: boom', $error);
        $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'role' => 'mentor']);
    }

    #[Test]
    public function area_training_staff_can_revoke_a_mentor_in_their_area(): void
    {
        $area = Area::factory()->create();
        $trainingStaff = User::factory()->create();
        $trainingStaff->roleAssignments()->create(['role' => 'training-staff', 'area_id' => $area->id]);

        $target = User::factory()->create();
        $target->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);

        DivisionApi::shouldReceive('removeMentor')->once()->andReturn(false);

        $error = (new RevokeRole)($trainingStaff, $target, 'mentor', $area);

        $this->assertNull($error);
        $this->assertDatabaseMissing('role_user', [
            'user_id' => $target->id, 'role' => 'mentor', 'area_id' => $area->id,
        ]);
    }
}
