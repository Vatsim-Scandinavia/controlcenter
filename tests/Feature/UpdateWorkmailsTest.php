<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateWorkmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_see_workmail_field_in_settings(): void
    {
        $area = Area::factory()->create();
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $area->id]);

        $response = $this->actingAs($director)->get(route('user.settings'));

        $response->assertOk();
        $response->assertSee('name="setting_workmail_address"', false);
    }

    public function test_director_keeps_workmail_while_roleless_user_loses_it(): void
    {
        $area = Area::factory()->create();

        $director = User::factory()->create(['setting_workmail_address' => 'director@example.test']);
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $area->id]);

        $roleless = User::factory()->create(['setting_workmail_address' => 'roleless@example.test']);

        $mentor = User::factory()->create(['setting_workmail_address' => 'mentor@example.test']);
        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);

        $this->artisan('update:workmails')->assertExitCode(0);

        $this->assertEquals('director@example.test', $director->fresh()->setting_workmail_address);
        $this->assertNull($roleless->fresh()->setting_workmail_address);
        $this->assertNull($mentor->fresh()->setting_workmail_address);
    }
}
