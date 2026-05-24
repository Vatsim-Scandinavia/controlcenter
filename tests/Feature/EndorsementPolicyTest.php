<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Endorsement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndorsementPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $moderator;

    private Area $area;

    protected function setUp(): void
    {
        parent::setUp();

        $this->area = Area::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->moderator = User::factory()->create();
        $this->moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $this->area->id]);
    }

    public function test_admin_can_create_visiting_endorsement(): void
    {
        $this->assertTrue($this->admin->can('create', [Endorsement::class, 'VISITING']));
    }

    public function test_moderator_cannot_create_visiting_endorsement(): void
    {
        $this->assertFalse($this->moderator->can('create', [Endorsement::class, 'VISITING']));
    }

    public function test_admin_can_create_examiner_endorsement(): void
    {
        $this->assertTrue($this->admin->can('create', [Endorsement::class, 'EXAMINER']));
    }

    public function test_moderator_cannot_create_examiner_endorsement(): void
    {
        $this->assertFalse($this->moderator->can('create', [Endorsement::class, 'EXAMINER']));
    }

    public function test_moderator_can_create_facility_endorsement(): void
    {
        $this->assertTrue($this->moderator->can('create', [Endorsement::class, 'FACILITY']));
    }

    public function test_moderator_cannot_delete_visiting_endorsement(): void
    {
        $endorsement = Endorsement::factory()->create(['type' => 'VISITING', 'user_id' => $this->admin->id]);

        $this->assertFalse($this->moderator->can('delete', $endorsement));
    }

    public function test_admin_can_delete_visiting_endorsement(): void
    {
        $endorsement = Endorsement::factory()->create(['type' => 'VISITING', 'user_id' => $this->moderator->id]);

        $this->assertTrue($this->admin->can('delete', $endorsement));
    }
}
