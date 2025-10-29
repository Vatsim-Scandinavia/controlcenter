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
        $this->adminUser->groups()->attach(1, ['area_id' => Area::factory()->create()->id]);
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
}
