<?php

namespace Tests\Feature\Statistics;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrainingStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $area = \App\Models\Area::factory()->create();
        $this->admin->groups()->attach(1, ['area_id' => $area->id]);
    }

    #[Test]
    public function it_can_render_training_statistics_page()
    {
        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.trainings');
    }

    #[Test]
    public function it_uses_default_dates_when_no_parameters_provided()
    {
        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertViewHas('startDate', null);
        $response->assertViewHas('endDate', null);
    }

    #[Test]
    public function it_uses_provided_start_date()
    {
        $startDate = Carbon::now()->subMonths(3)->format('Y-m-d');

        $response = $this->actingAs($this->admin)->get(route('reports.trainings', ['start_date' => $startDate]));

        $response->assertStatus(200);
        $response->assertViewHas('startDate', function ($date) use ($startDate) {
            return $date->format('Y-m-d') === $startDate;
        });
        // End date should remain null if not provided
        $response->assertViewHas('endDate', null);
    }

    #[Test]
    public function it_uses_provided_end_date()
    {
        $endDate = Carbon::now()->subMonth(1)->format('Y-m-d');

        $response = $this->actingAs($this->admin)->get(route('reports.trainings', ['end_date' => $endDate]));

        $response->assertStatus(200);
        // Start date should remain null if not provided
        $response->assertViewHas('startDate', null);
        $response->assertViewHas('endDate', function ($date) use ($endDate) {
            return $date->format('Y-m-d') === $endDate;
        });
    }

    #[Test]
    public function it_uses_both_provided_dates()
    {
        $startDate = Carbon::now()->subMonths(5)->format('Y-m-d');
        $endDate = Carbon::now()->subMonths(2)->format('Y-m-d');

        $response = $this->actingAs($this->admin)->get(route('reports.trainings', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('startDate', function ($date) use ($startDate) {
            return $date->format('Y-m-d') === $startDate;
        });
        $response->assertViewHas('endDate', function ($date) use ($endDate) {
            return $date->format('Y-m-d') === $endDate;
        });
    }

    #[Test]
    public function it_shows_error_when_start_date_is_after_end_date()
    {
        $startDate = Carbon::now()->format('Y-m-d');
        $endDate = Carbon::now()->subDay()->format('Y-m-d');

        $response = $this->actingAs($this->admin)->get(route('reports.trainings', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Start date must be before end date');
    }
}
