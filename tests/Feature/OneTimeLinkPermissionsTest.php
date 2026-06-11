<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OneTimeLinkPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_buddy_holds_report_link_permission_and_moderator_does_not(): void
    {
        $area = Area::factory()->create();

        $buddy = User::factory()->create();
        $buddy->roleAssignments()->create(['role' => 'buddy', 'area_id' => $area->id]);

        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $this->assertTrue($buddy->hasPermission('use-report-one-time-link'));
        $this->assertFalse($moderator->hasPermission('use-report-one-time-link'));
    }

    public function test_director_holds_update_training_in_their_area(): void
    {
        $area = Area::factory()->create();
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $area->id]);

        $this->assertTrue($director->hasPermission('update-training', $area));
    }
}
