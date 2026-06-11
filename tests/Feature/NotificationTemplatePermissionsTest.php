<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use App\Policies\NotificationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTemplatePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_view_and_modify_templates_in_their_area(): void
    {
        $area = Area::factory()->create();
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $area->id]);

        $policy = new NotificationPolicy;
        $this->assertTrue($policy->viewTemplates($director));
        $this->assertTrue($policy->modifyAreaTemplate($director, $area));
    }

    public function test_admin_can_modify_any_areas_templates(): void
    {
        $area = Area::factory()->create();
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $policy = new NotificationPolicy;
        $this->assertTrue($policy->viewTemplates($admin));
        $this->assertTrue($policy->modifyAreaTemplate($admin, $area));
    }

    public function test_area_moderator_cannot_modify_other_areas_templates(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area->id]);

        $policy = new NotificationPolicy;
        $this->assertTrue($policy->modifyAreaTemplate($moderator, $area));
        $this->assertFalse($policy->modifyAreaTemplate($moderator, $otherArea));
    }
}
