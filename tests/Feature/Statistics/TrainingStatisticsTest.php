<?php

namespace Tests\Feature\Statistics;

use App\Models\Area;
use App\Models\Position;
use App\Models\Rating;
use App\Models\Training;
use App\Models\TrainingExamination;
use App\Models\TrainingReport;
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
        $this->area = Area::factory()->create();
        $this->admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);
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

    /**
     * Create a training report (session) for the given training.
     */
    private function createReport(Training $training, bool $draft, Carbon $activityDate): TrainingReport
    {
        return TrainingReport::factory()->create([
            'training_id' => $training->id,
            'position' => Position::factory()->create()->callsign,
            'draft' => $draft,
            'created_at' => $activityDate,
            'published_at' => $draft ? null : $activityDate,
            'report_date' => $activityDate,
        ]);
    }

    /**
     * Create a training with the given status, area and ratings.
     *
     * @param  array<int>  $ratingIds
     */
    private function createTraining(int $status, array $ratingIds, ?Carbon $closedAt = null): Training
    {
        $training = Training::factory()->create([
            'user_id' => $this->admin->id,
            'area_id' => $this->area->id,
            'type' => 1,
            'status' => $status,
            'closed_at' => $closedAt,
        ]);
        $training->ratings()->attach($ratingIds);

        return $training;
    }

    #[Test]
    public function it_counts_only_non_draft_reports_in_session_volume()
    {
        $rating = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);
        $training = $this->createTraining(0, [$rating->id]);

        $this->createReport($training, draft: false, activityDate: now()->subMonth());
        $this->createReport($training, draft: false, activityDate: now()->subMonth());
        $this->createReport($training, draft: true, activityDate: now()->subMonth());

        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertViewHas('sessionsPerRating', function ($sessionsPerRating) {
            return $sessionsPerRating['S2']['volume'] === 2;
        });
    }

    #[Test]
    public function it_keeps_session_volume_within_the_date_window()
    {
        $rating = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);
        $training = $this->createTraining(0, [$rating->id]);

        $start = now()->subMonths(2)->startOfMonth()->addDays(10);
        $end = $start->copy()->addDays(2)->endOfDay();

        $this->createReport($training, draft: false, activityDate: $start->copy()->addDay());
        $this->createReport($training, draft: false, activityDate: $start->copy()->subDays(10));

        $response = $this->actingAs($this->admin)->get(route('reports.trainings', [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('sessionsPerRating', function ($sessionsPerRating) {
            return $sessionsPerRating['S2']['volume'] === 1;
        });
    }

    #[Test]
    public function it_respects_the_area_filter_for_session_volume()
    {
        $rating = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);
        $otherArea = Area::factory()->create();

        $training = $this->createTraining(0, [$rating->id]);
        $this->createReport($training, draft: false, activityDate: now()->subMonth());

        $otherTraining = Training::factory()->create([
            'user_id' => $this->admin->id,
            'area_id' => $otherArea->id,
            'type' => 1,
            'status' => 0,
        ]);
        $otherTraining->ratings()->attach($rating->id);
        $this->createReport($otherTraining, draft: false, activityDate: now()->subMonth());

        $response = $this->actingAs($this->admin)->get(route('reports.training.area', ['id' => $this->area->id]));

        $response->assertStatus(200);
        $response->assertViewHas('sessionsPerRating', function ($sessionsPerRating) {
            return $sessionsPerRating['S2']['volume'] === 1;
        });
    }

    #[Test]
    public function it_computes_average_and_median_over_ended_trainings_only()
    {
        $rating = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);

        // Completed training with 4 reports.
        $completed = $this->createTraining(-1, [$rating->id], closedAt: now()->subMonth());
        for ($i = 0; $i < 4; $i++) {
            $this->createReport($completed, draft: false, activityDate: now()->subMonth());
        }

        // Closed training with 2 reports.
        $closed = $this->createTraining(-2, [$rating->id], closedAt: now()->subMonth());
        for ($i = 0; $i < 2; $i++) {
            $this->createReport($closed, draft: false, activityDate: now()->subMonth());
        }

        // In-progress training with 100 reports - must be excluded from avg/median.
        $inProgress = $this->createTraining(1, [$rating->id]);
        for ($i = 0; $i < 100; $i++) {
            $this->createReport($inProgress, draft: false, activityDate: now()->subMonth());
        }

        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertViewHas('sessionsPerRating', function ($sessionsPerRating) {
            // Sample [4, 2] -> mean 3.0, median 3.0
            return $sessionsPerRating['S2']['average'] === 3.0
                && $sessionsPerRating['S2']['median'] === 3.0;
        });
    }

    #[Test]
    public function it_computes_median_for_odd_sample_sizes()
    {
        $rating = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);

        foreach ([1, 5, 9] as $count) {
            $training = $this->createTraining(-1, [$rating->id], closedAt: now()->subMonth());
            for ($i = 0; $i < $count; $i++) {
                $this->createReport($training, draft: false, activityDate: now()->subMonth());
            }
        }

        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertViewHas('sessionsPerRating', function ($sessionsPerRating) {
            // Sample [1, 5, 9] -> median 5.0
            return $sessionsPerRating['S2']['median'] === 5.0;
        });
    }

    #[Test]
    public function it_computes_median_for_even_sample_sizes()
    {
        $rating = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);

        foreach ([2, 4, 6, 8] as $count) {
            $training = $this->createTraining(-1, [$rating->id], closedAt: now()->subMonth());
            for ($i = 0; $i < $count; $i++) {
                $this->createReport($training, draft: false, activityDate: now()->subMonth());
            }
        }

        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertViewHas('sessionsPerRating', function ($sessionsPerRating) {
            // Sample [2, 4, 6, 8] -> median (4 + 6) / 2 = 5.0
            return $sessionsPerRating['S2']['median'] === 5.0;
        });
    }

    #[Test]
    public function it_attributes_sessions_to_each_linked_rating()
    {
        $s2 = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);
        $s3 = Rating::factory()->create(['name' => 'S3', 'vatsim_rating' => 4]);

        $training = $this->createTraining(-1, [$s2->id, $s3->id], closedAt: now()->subMonth());
        $this->createReport($training, draft: false, activityDate: now()->subMonth());
        $this->createReport($training, draft: false, activityDate: now()->subMonth());

        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertViewHas('sessionsPerRating', function ($sessionsPerRating) {
            return $sessionsPerRating['S2']['volume'] === 2
                && $sessionsPerRating['S3']['volume'] === 2
                && $sessionsPerRating['S2']['average'] === 2.0
                && $sessionsPerRating['S3']['average'] === 2.0;
        });
    }

    #[Test]
    public function it_orders_ratings_by_rating_id_regardless_of_data_order()
    {
        $s1 = Rating::factory()->create(['name' => 'S1', 'vatsim_rating' => 2]);
        $s2 = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);
        $s3 = Rating::factory()->create(['name' => 'S3', 'vatsim_rating' => 4]);

        // Create the data in a non-id order to prove ordering is by rating id,
        // not by insertion or query result order.
        foreach ([$s3, $s1, $s2] as $rating) {
            $training = $this->createTraining(-1, [$rating->id], closedAt: now()->subMonth());
            $this->createReport($training, draft: false, activityDate: now()->subMonth());
        }

        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertViewHas('sessionsPerRating', function ($sessionsPerRating) {
            return array_keys($sessionsPerRating) === ['S1', 'S2', 'S3'];
        });
    }

    #[Test]
    public function it_filters_out_all_zero_ratings()
    {
        $s2 = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);
        Rating::factory()->create(['name' => 'S3', 'vatsim_rating' => 4]);

        $training = $this->createTraining(-1, [$s2->id], closedAt: now()->subMonth());
        $this->createReport($training, draft: false, activityDate: now()->subMonth());

        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertViewHas('sessionsPerRating', function ($sessionsPerRating) {
            return array_key_exists('S2', $sessionsPerRating)
                && ! array_key_exists('S3', $sessionsPerRating);
        });
    }

    #[Test]
    public function it_renders_the_sessions_per_rating_chart()
    {
        $rating = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);
        $training = $this->createTraining(-1, [$rating->id], closedAt: now()->subMonth());
        $this->createReport($training, draft: false, activityDate: now()->subMonth());

        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertSee('sessionsPerRating', false);
        $response->assertSee('Training sessions per rating');
    }

    #[Test]
    public function it_excludes_zero_session_trainings_from_average_and_median()
    {
        $rating = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);

        // Completed training with 4 sessions.
        $withReports = $this->createTraining(-1, [$rating->id], closedAt: now()->subMonth());
        for ($i = 0; $i < 4; $i++) {
            $this->createReport($withReports, draft: false, activityDate: now()->subMonth());
        }

        // Closed training that recorded zero sessions (queue drop-out) must NOT
        // count as a 0 data point - it never actually trained.
        $this->createTraining(-2, [$rating->id], closedAt: now()->subMonth());

        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertViewHas('sessionsPerRating', function ($sessionsPerRating) {
            // Sample [4] (the zero-session closure excluded) -> mean 4.0, median 4.0; volume still 4.
            return $sessionsPerRating['S2']['volume'] === 4
                && $sessionsPerRating['S2']['average'] === 4.0
                && $sessionsPerRating['S2']['median'] === 4.0;
        });
    }

    #[Test]
    public function it_reports_null_average_and_median_when_no_ended_training_has_sessions()
    {
        $rating = Rating::factory()->create(['name' => 'S2', 'vatsim_rating' => 3]);

        // In-progress training with sessions: contributes volume but no ended sample.
        $inProgress = $this->createTraining(1, [$rating->id]);
        $this->createReport($inProgress, draft: false, activityDate: now()->subMonth());
        $this->createReport($inProgress, draft: false, activityDate: now()->subMonth());

        // Closed training with zero sessions: excluded from the sample entirely.
        $this->createTraining(-2, [$rating->id], closedAt: now()->subMonth());

        $response = $this->actingAs($this->admin)->get(route('reports.trainings'));

        $response->assertStatus(200);
        $response->assertViewHas('sessionsPerRating', function ($sessionsPerRating) {
            // Volume present (2), but no qualifying sample -> markers omitted (null), not 0.
            return $sessionsPerRating['S2']['volume'] === 2
                && $sessionsPerRating['S2']['average'] === null
                && $sessionsPerRating['S2']['median'] === null;
        });
    }
}
