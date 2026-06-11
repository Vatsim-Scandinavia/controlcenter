<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Sweatbook;
use App\Models\User;
use App\Policies\SweatbookPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SweatbookPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_view_create_and_update_sweatbook(): void
    {
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => null]);
        $otherUsersBooking = new Sweatbook;
        $otherUsersBooking->user_id = User::factory()->create()->id;

        $policy = new SweatbookPolicy;
        $this->assertTrue($policy->view($director));
        $this->assertTrue($policy->create($director));
        $this->assertTrue($policy->update($director, $otherUsersBooking));
    }

    public function test_mentor_can_view_but_not_update_others_bookings(): void
    {
        $area = Area::factory()->create();
        $mentor = User::factory()->create();
        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area->id]);
        $otherUsersBooking = new Sweatbook;
        $otherUsersBooking->user_id = User::factory()->create()->id;

        $policy = new SweatbookPolicy;
        $this->assertTrue($policy->view($mentor));
        $this->assertFalse($policy->update($mentor, $otherUsersBooking));
    }
}
