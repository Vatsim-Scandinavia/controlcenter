<?php

namespace Tests\Feature\Statistics;

use App\Models\Rating;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrainingStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->area = \App\Models\Area::factory()->create();
        $this->admin->roleAssignments()->create(['role' => 'admin', 'area_id' => $this->area->id]);
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

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['end_date']);
    }

    #[Test]
    public function it_rejects_date_ranges_longer_than_twenty_four_months()
    {
        $startDate = Carbon::now()->subMonths(30)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->admin)->get(route('reports.trainings', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['end_date']);
    }

    #[Test]
    public function it_keeps_monthly_stats_within_selected_date_window()
    {
        $rating = Rating::factory()->create(['vatsim_rating' => 3]);
        $startDate = now()->subMonths(2)->startOfMonth()->addDays(10);
        $endDate = $startDate->copy()->addDays(2)->endOfDay();

        $outsideTraining = Training::factory()->create([
            'user_id' => $this->admin->id,
            'area_id' => $this->area->id,
            'type' => 1,
            'status' => 0,
            'created_at' => $startDate->copy()->subDays(5),
            'closed_at' => null,
        ]);
        $outsideTraining->ratings()->attach($rating->id);

        $insideTraining = Training::factory()->create([
            'user_id' => $this->admin->id,
            'area_id' => $this->area->id,
            'type' => 1,
            'status' => 0,
            'created_at' => $startDate->copy()->addDay(),
            'closed_at' => null,
        ]);
        $insideTraining->ratings()->attach($rating->id);

        $response = $this->actingAs($this->admin)->get(route('reports.trainings', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('newRequests', function ($newRequests) {
            return array_sum(collect($newRequests)->flatten()->all()) === 1;
        });
    }

    #[Test]
    public function it_includes_endorsement_trainings_in_pass_fail_statistics()
    {
        $endorsementRating = Rating::factory()->create([
            'vatsim_rating' => null,
            'endorsement_type' => 'T1',
        ]);

        $training = Training::factory()->create([
            'user_id' => $this->admin->id,
            'area_id' => $this->area->id,
            'type' => 5,
        ]);
        $training->ratings()->attach($endorsementRating->id);

        TrainingExamination::factory()->create([
            'training_id' => $training->id,
            'result' => 'PASSED',
            'examination_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));
        $response->assertStatus(200);
        $response->assertViewHas('passedExamRequests', function ($passedExamRequests) {
            return array_sum(collect($passedExamRequests)->flatten()->all()) === 1;
        });
        $response->assertViewHas('failedExamRequests', function ($failedExamRequests) {
            return array_sum(collect($failedExamRequests)->flatten()->all()) === 0;
        });
    }
}
