<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\TrainingStatus;
use App\Helpers\VatsimRating;
use App\Models\Area;
use App\Models\Rating;
use App\Models\Training;
use App\Models\User;
use App\Services\TrainingQueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\CreatesRoleAssignedUsers;
use Tests\TestCase;

class TrainingQueuePositionTest extends TestCase
{
    use CreatesRoleAssignedUsers;
    use RefreshDatabase;

    private function queuedTraining(array $ratings, string $appliedAt, int $areaId = 1, array $attributes = []): Training
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create()->id,
            'area_id' => $areaId,
            'status' => TrainingStatus::IN_QUEUE->value,
            'created_at' => Carbon::parse($appliedAt),
            'started_at' => null,
            'closed_at' => null,
            ...$attributes,
        ]);
        $training->ratings()->attach(collect($ratings)->pluck('id'));

        return $training->fresh();
    }

    private function vatsimRating(string $name): Rating
    {
        return Rating::where('name', $name)->firstOrFail();
    }

    private function endorsement(string $name): Rating
    {
        return Rating::factory()->create(['name' => $name, 'vatsim_rating' => null]);
    }

    private function positionOf(Training $training): ?array
    {
        return app(TrainingQueueService::class)->positionOf($training);
    }

    #[Test]
    public function position_follows_the_application_date(): void
    {
        $s2 = $this->vatsimRating('S2');
        $second = $this->queuedTraining([$s2], '2026-02-01');
        $first = $this->queuedTraining([$s2], '2026-01-01');

        $this->assertSame(['position' => 1, 'total' => 2, 'name' => 'S2'], $this->positionOf($first));
        $this->assertSame(['position' => 2, 'total' => 2, 'name' => 'S2'], $this->positionOf($second));
    }

    #[Test]
    public function equal_application_dates_get_distinct_positions(): void
    {
        $s2 = $this->vatsimRating('S2');
        $first = $this->queuedTraining([$s2], '2026-01-01 12:00:00');
        $second = $this->queuedTraining([$s2], '2026-01-01 12:00:00');

        $this->assertSame(1, $this->positionOf($first)['position']);
        $this->assertSame(2, $this->positionOf($second)['position']);
    }

    #[Test]
    public function other_areas_levels_and_statuses_are_not_counted(): void
    {
        $s2 = $this->vatsimRating('S2');
        $this->queuedTraining([$s2], '2025-01-01', 2);
        $this->queuedTraining([$this->vatsimRating('S3')], '2025-01-01');
        $this->queuedTraining([$s2], '2025-01-01', 1, ['status' => TrainingStatus::PRE_TRAINING->value]);
        $this->queuedTraining([$s2], '2025-01-01', 1, ['status' => TrainingStatus::CLOSED_BY_STAFF->value]);

        $training = $this->queuedTraining([$s2], '2026-01-01');

        $this->assertSame(['position' => 1, 'total' => 1, 'name' => 'S2'], $this->positionOf($training));
    }

    #[Test]
    public function a_bundle_queues_under_its_highest_vatsim_rating(): void
    {
        $s3 = $this->vatsimRating('S3');
        $tier = $this->endorsement('T1 ENGM APP');
        $this->queuedTraining([$s3], '2026-01-01');
        $this->queuedTraining([$this->vatsimRating('S2'), $s3], '2026-02-01');
        $bundle = $this->queuedTraining([$s3, $tier], '2026-03-01');
        $this->queuedTraining([$tier], '2026-01-01');

        $this->assertSame(['position' => 3, 'total' => 3, 'name' => 'S3'], $this->positionOf($bundle));
    }

    #[Test]
    public function endorsement_only_trainings_queue_by_their_full_set_of_ratings(): void
    {
        $towerRating = $this->endorsement('T1 ENGM TWR');
        $approachRating = $this->endorsement('T1 ENGM APP');
        $this->queuedTraining([$towerRating], '2026-01-01');
        $this->queuedTraining([$approachRating, $towerRating], '2026-02-01');
        $training = $this->queuedTraining([$towerRating, $approachRating], '2026-03-01');

        $this->assertSame(
            ['position' => 2, 'total' => 2, 'name' => 'T1 ENGM TWR + T1 ENGM APP'],
            $this->positionOf($training),
        );
    }

    #[Test]
    public function a_paused_training_keeps_its_place(): void
    {
        $s2 = $this->vatsimRating('S2');
        $paused = $this->queuedTraining([$s2], '2026-01-01', 1, ['paused_at' => now()]);
        $training = $this->queuedTraining([$s2], '2026-02-01');

        $this->assertSame(1, $this->positionOf($paused)['position']);
        $this->assertSame(2, $this->positionOf($training)['position']);
    }

    #[Test]
    public function a_training_back_in_queue_returns_to_its_original_place(): void
    {
        $s2 = $this->vatsimRating('S2');
        $training = $this->queuedTraining([$s2], '2026-01-01');
        $this->queuedTraining([$s2], '2026-02-01');

        $training->updateStatus(TrainingStatus::PRE_TRAINING);
        $this->assertNull($this->positionOf($training->fresh()));

        $training->updateStatus(TrainingStatus::IN_QUEUE);
        $this->assertSame(1, $this->positionOf($training->fresh())['position']);
    }

    #[Test]
    public function a_training_that_left_the_queue_since_it_was_loaded_has_no_position(): void
    {
        $s2 = $this->vatsimRating('S2');
        $this->queuedTraining([$s2], '2026-01-01');
        $training = $this->queuedTraining([$s2], '2026-02-01');

        Training::whereKey($training->id)->update(['status' => TrainingStatus::CLOSED_BY_STAFF->value]);

        $this->assertNull($this->positionOf($training));
    }

    #[Test]
    public function dashboard_shows_the_application_date_and_position(): void
    {
        $s2 = $this->vatsimRating('S2');
        $this->queuedTraining([$s2], '2026-01-01');
        $training = $this->queuedTraining([$s2], '2026-03-12');

        $this->actingAs($training->user)->get(route('dashboard'))
            ->assertSuccessful()
            ->assertSeeText('Applied 12/03/2026')
            ->assertSeeText('Queue position 2 of 2')
            ->assertSee('Place among S2 requests waiting in', false);
    }

    #[Test]
    public function open_requests_show_no_period_for_a_training_in_queue(): void
    {
        $this->queuedTraining([$this->vatsimRating('S2')], '2026-01-01');

        $this->actingAs($this->admin())->get(route('requests'))
            ->assertSuccessful()
            ->assertSeeText('N/A')
            ->assertDontSeeText('Training not started');
    }

    #[Test]
    public function dashboard_shows_no_queue_details_for_a_training_in_progress(): void
    {
        $training = $this->queuedTraining([$this->vatsimRating('S2')], '2026-01-01', 1, [
            'status' => TrainingStatus::ACTIVE_TRAINING->value,
            'started_at' => Carbon::parse('2026-02-01'),
        ]);

        $this->actingAs($training->user)->get(route('dashboard'))
            ->assertSuccessful()
            ->assertDontSeeText('Applied 01/01/2026')
            ->assertDontSeeText('Queue position');
    }

    #[Test]
    public function training_page_shows_the_queue_position(): void
    {
        $training = $this->queuedTraining([$this->vatsimRating('S2')], '2026-01-01');

        $this->actingAs($training->user)->get(route('training.show', $training))
            ->assertSuccessful()
            ->assertSeeInOrder(['Queue position', '1 of 1'])
            ->assertSee('Place among S2 requests waiting in ' . $training->area->name, false);
    }

    #[Test]
    public function training_page_hides_the_queue_position_outside_the_queue(): void
    {
        $training = $this->queuedTraining([$this->vatsimRating('S2')], '2026-01-01', 1, [
            'status' => TrainingStatus::ACTIVE_TRAINING->value,
            'started_at' => Carbon::parse('2026-02-01'),
        ]);

        $this->actingAs($training->user)->get(route('training.show', $training))
            ->assertSuccessful()
            ->assertDontSeeText('Queue position');
    }

    #[Test]
    public function apply_page_exposes_the_queue_of_every_option(): void
    {
        $s2 = $this->vatsimRating('S2');
        $this->queuedTraining([$s2], '2026-01-01');
        $this->queuedTraining([$s2], '2026-02-01');

        $option = $this->applyPayload()[1]['data']->firstWhere('id', $s2->id);

        $this->assertSame('S2', $option->queue_name);
        $this->assertSame(2, $option->queue_size);
    }

    #[Test]
    public function apply_page_exposes_an_empty_queue(): void
    {
        $s2 = $this->vatsimRating('S2');

        $option = $this->applyPayload()[1]['data']->firstWhere('id', $s2->id);

        $this->assertSame(0, $option->queue_size);
    }

    #[Test]
    public function apply_page_counts_a_bundle_in_the_queue_of_its_highest_vatsim_rating(): void
    {
        $s2 = $this->vatsimRating('S2');
        $tier = $this->endorsement('T1 ENGM TWR');
        $area = Area::findOrFail(1);
        $area->ratings()->updateExistingPivot($s2->id, ['allow_bundling' => true]);
        $area->ratings()->attach($tier->id, ['allow_bundling' => true]);
        $this->queuedTraining([$s2], '2026-01-01');

        $bundle = $this->applyPayload()[1]['data']->firstWhere('name', 'S2 + T1 ENGM TWR');

        $this->assertSame('S2', $bundle['queue_name']);
        $this->assertSame(1, $bundle['queue_size']);
    }

    #[Test]
    public function pages_show_a_position_lower_than_the_total(): void
    {
        $s2 = $this->vatsimRating('S2');
        $training = $this->queuedTraining([$s2], '2026-01-01');
        $this->queuedTraining([$s2], '2026-02-01');

        $this->actingAs($training->user)->get(route('dashboard'))
            ->assertSeeText('Queue position 1 of 2');
        $this->actingAs($training->user)->get(route('training.show', $training))
            ->assertSeeInOrder(['Queue position', '1 of 2']);
    }

    #[Test]
    public function queue_tooltip_is_active_on_dashboard_and_training_page(): void
    {
        $training = $this->queuedTraining([$this->vatsimRating('S2')], '2026-01-01');
        $tooltip = 'data-bs-toggle="tooltip" data-bs-placement="top" title="Place among S2 requests waiting in ' . $training->area->name . ', ordered by application date. It may change as other requests are processed."';

        foreach ([route('dashboard'), route('training.show', $training)] as $url) {
            $this->actingAs($training->user)->get($url)
                ->assertSuccessful()
                ->assertSee($tooltip, false)
                ->assertSee('new bootstrap.Tooltip', false);
        }
    }

    #[Test]
    public function training_page_shows_no_period_for_a_training_in_queue(): void
    {
        $training = $this->queuedTraining([$this->vatsimRating('S2')], '2026-01-01');

        $this->actingAs($training->user)->get(route('training.show', $training))
            ->assertSuccessful()
            ->assertDontSeeText('Training not started')
            ->assertSeeInOrder(['Period', 'N/A', 'Applied'], false);
    }

    #[Test]
    public function dashboard_shows_no_period_for_an_unstarted_training_outside_the_queue(): void
    {
        $training = $this->queuedTraining([$this->vatsimRating('S2')], '2026-01-01', 1, [
            'status' => TrainingStatus::PRE_TRAINING->value,
        ]);

        $this->actingAs($training->user)->get(route('dashboard'))
            ->assertSuccessful()
            ->assertDontSeeText('Training not started')
            ->assertDontSeeText('Applied 01/01/2026')
            ->assertSeeInOrder(['<th>Period</th>', $training->area->name, 'N/A', $training->status->label()], false);
    }

    #[Test]
    public function mentor_page_shows_no_period_for_an_unstarted_training(): void
    {
        $mentor = $this->admin();
        $training = $this->queuedTraining([$this->vatsimRating('S2')], '2026-01-01', 1, [
            'status' => TrainingStatus::PRE_TRAINING->value,
        ]);
        $training->mentors()->attach($mentor->id, ['expire_at' => now()->addYear()]);

        $this->actingAs($mentor)->get(route('mentor'))
            ->assertSuccessful()
            ->assertSeeText($training->user->name)
            ->assertSeeText('N/A')
            ->assertDontSeeText('Training not started');
    }

    #[Test]
    public function apply_page_counts_each_queue_per_area(): void
    {
        $s2 = $this->vatsimRating('S2');
        Area::findOrFail(2)->ratings()->syncWithoutDetaching([$s2->id]);
        $this->queuedTraining([$s2], '2026-01-01', 1);
        $this->queuedTraining([$s2], '2026-01-01', 2);
        $this->queuedTraining([$s2], '2026-02-01', 2);

        $payload = $this->applyPayload();

        $this->assertSame(1, $payload[1]['data']->firstWhere('id', $s2->id)->queue_size);
        $this->assertSame(2, $payload[2]['data']->firstWhere('id', $s2->id)->queue_size);
    }

    #[Test]
    public function apply_page_counts_each_bundle_queue_per_area_including_an_empty_one(): void
    {
        $s2 = $this->vatsimRating('S2');
        $tier = $this->endorsement('T1 ENGM TWR');
        foreach ([1, 2] as $areaId) {
            $area = Area::findOrFail($areaId);
            $area->ratings()->syncWithoutDetaching([$s2->id, $tier->id]);
            $area->ratings()->updateExistingPivot($s2->id, ['allow_bundling' => true]);
            $area->ratings()->updateExistingPivot($tier->id, ['allow_bundling' => true]);
        }
        $this->queuedTraining([$s2], '2026-01-01', 2);

        $payload = $this->applyPayload();

        $this->assertSame(0, $payload[1]['data']->firstWhere('name', 'S2 + T1 ENGM TWR')['queue_size']);
        $this->assertSame(1, $payload[2]['data']->firstWhere('name', 'S2 + T1 ENGM TWR')['queue_size']);
    }

    #[Test]
    public function apply_page_counts_an_endorsement_bundle_by_its_full_set(): void
    {
        $towerRating = $this->endorsement('T1 ENGM TWR');
        $approachRating = $this->endorsement('T1 ENGM APP');
        Area::findOrFail(1)->ratings()->attach([
            $towerRating->id => ['allow_bundling' => true],
            $approachRating->id => ['allow_bundling' => true],
        ]);
        $this->queuedTraining([$approachRating, $towerRating], '2026-01-01');
        $this->queuedTraining([$towerRating], '2026-01-01');

        $bundle = $this->applyPayload()[1]['data']->firstWhere('name', 'T1 ENGM TWR + T1 ENGM APP');

        $this->assertSame('T1 ENGM TWR + T1 ENGM APP', $bundle['queue_name']);
        $this->assertSame(1, $bundle['queue_size']);
    }

    #[Test]
    public function apply_page_exposes_the_queue_of_a_single_endorsement(): void
    {
        $towerRating = $this->endorsement('T1 ENGM TWR');
        Area::findOrFail(1)->ratings()->attach($towerRating->id);
        $this->queuedTraining([$towerRating], '2026-01-01');

        $option = $this->applyPayload()[1]['data']->firstWhere('id', $towerRating->id);

        $this->assertSame('T1 ENGM TWR', $option->queue_name);
        $this->assertSame(1, $option->queue_size);
    }

    #[Test]
    public function apply_page_renders_the_queue_data_for_the_form(): void
    {
        $s2 = $this->vatsimRating('S2');
        $this->queuedTraining([$s2], '2026-01-01');
        $this->queuedTraining([$s2], '2026-02-01');

        $this->applyPayload();

        $this->get(route('training.apply'))
            ->assertSee('"queue_name":"S2"', false)
            ->assertSee('"queue_size":2', false);
    }

    #[Test]
    public function queue_keys_do_not_collide(): void
    {
        $service = app(TrainingQueueService::class);
        $rating = fn (int $id) => (new Rating)->forceFill(['id' => $id, 'vatsim_rating' => null]);

        $this->assertNotSame(
            $service->queueKey(1, collect([$rating(1), $rating(230)])),
            $service->queueKey(1, collect([$rating(12), $rating(30)])),
        );
        $this->assertNotSame(
            $service->queueKey(1, collect([$rating(23)])),
            $service->queueKey(12, collect([$rating(3)])),
        );
    }

    /**
     * Open the apply page as an eligible student and get its options.
     *
     * @return array<int, array<string, mixed>>
     */
    private function applyPayload(): array
    {
        Http::fake(['api.vatsim.net/*' => Http::response([], 404)]);

        Setting::set('trainingEnabled', true);
        Setting::set('trainingSubDivisions', 'SCA');
        Setting::set('atcActivityBasedOnTotalHours', false);

        $student = User::factory()->create([
            'rating' => VatsimRating::OBS->value,
            'division' => config('app.owner_code'),
            'subdivision' => 'SCA',
        ]);

        return $this->actingAs($student)->get(route('training.apply'))
            ->assertSuccessful()
            ->viewData('payload');
    }
}
