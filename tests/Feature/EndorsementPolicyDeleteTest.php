<?php

namespace Tests\Feature;

use App\Models\Endorsement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndorsementPolicyDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function endorsement(string $type): Endorsement
    {
        // `revoked`/`expired` are real columns the factory doesn't set; force them
        // off so the endorsement is eligible for deletion. `type` other than
        // VISITING/EXAMINER is treated as a solo endorsement by the policy.
        $endorsement = Endorsement::factory()->create(['type' => $type]);
        $endorsement->forceFill(['revoked' => false, 'expired' => false])->save();

        return $endorsement;
    }

    public function test_moderator_can_delete_solo_but_not_visiting_or_examiner(): void
    {
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => null]);

        $this->assertTrue($moderator->can('delete', $this->endorsement('FACILITY')));
        $this->assertFalse($moderator->can('delete', $this->endorsement('VISITING')));
        $this->assertFalse($moderator->can('delete', $this->endorsement('EXAMINER')));
    }

    public function test_director_can_delete_every_type(): void
    {
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => null]);

        $this->assertTrue($director->can('delete', $this->endorsement('FACILITY')));
        $this->assertTrue($director->can('delete', $this->endorsement('VISITING')));
        $this->assertTrue($director->can('delete', $this->endorsement('EXAMINER')));
    }
}
