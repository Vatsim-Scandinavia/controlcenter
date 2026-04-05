<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        $this->adminUser->roleAssignments()->create(['role' => 'admin', 'area_id' => Area::factory()->create()->id]);
    }

    public static function reportRoutesProvider(): array
    {
        return [
            'access' => ['reports.access'],
            'trainings' => ['reports.trainings'],
            'activities' => ['reports.activities'],
            'mentors' => ['reports.mentors'],
            'feedback' => ['reports.feedback'],
        ];
    }

    #[Test]
    #[DataProvider('reportRoutesProvider')]
    public function can_visit_report_page(string $routeName): void
    {
        $response = $this->actingAs($this->adminUser)->get(route($routeName));
        $response->assertOk();
    }

    public function test_moderator_sees_only_mentors_in_their_area(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();

        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $area1->id]);

        // Mentor in moderator's area
        $mentorInArea = User::factory()->create();
        $mentorInArea->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area1->id]);

        // Mentor in a different area
        $mentorElsewhere = User::factory()->create();
        $mentorElsewhere->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area2->id]);

        $response = $this->actingAs($moderator)->get(route('reports.mentors'));

        $response->assertStatus(200);
        $response->assertViewHas('mentors', function ($mentors) use ($mentorInArea, $mentorElsewhere) {
            return $mentors->contains($mentorInArea) && ! $mentors->contains($mentorElsewhere);
        });
    }

    public function test_admin_sees_all_mentors(): void
    {
        $area1 = Area::factory()->create();
        $area2 = Area::factory()->create();

        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $mentor1 = User::factory()->create();
        $mentor1->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area1->id]);

        $mentor2 = User::factory()->create();
        $mentor2->roleAssignments()->create(['role' => 'mentor', 'area_id' => $area2->id]);

        $response = $this->actingAs($admin)->get(route('reports.mentors'));

        $response->assertStatus(200);
        $response->assertViewHas('mentors', function ($mentors) use ($mentor1, $mentor2) {
            return $mentors->contains($mentor1) && $mentors->contains($mentor2);
        });
    }
}
