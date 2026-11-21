<?php

namespace Tests\Feature\Api\V1;

use App\Facades\DivisionApi;
use App\Helpers\TrainingStatus;
use App\Models\ApiKey;
use App\Models\Area;
use App\Models\Endorsement;
use App\Models\Rating;
use App\Models\Training;
use App\Models\User;
use App\Notifications\TrainingClosedNotification;
use App\Notifications\TrainingCreatedNotification;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TrainingEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function editKey(): string
    {
        ApiKey::firstOrCreate(['id' => 'training-edit-key'], ['name' => 't', 'read_only' => false, 'created_at' => now()]);

        return 'training-edit-key';
    }

    private function readOnlyKey(): string
    {
        ApiKey::firstOrCreate(['id' => 'training-ro-key'], ['name' => 't', 'read_only' => true, 'created_at' => now()]);

        return 'training-ro-key';
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeTraining(TrainingStatus $status = TrainingStatus::ACTIVE_TRAINING, array $attributes = []): Training
    {
        return Training::factory()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'area_id' => Area::factory()->create()->id,
            'status' => $status->value,
            'type' => 1,
        ], $attributes));
    }

    private function vatsimRating(): Rating
    {
        return Rating::factory()->create(['vatsim_rating' => 2]);
    }

    private function facilityRating(): Rating
    {
        return Rating::factory()->create(['vatsim_rating' => null]);
    }

    private function makeMentor(Training $training): User
    {
        $mentor = User::factory()->create();
        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => $training->area_id]);

        return $mentor;
    }

    public function test_training_endpoints_reject_missing_key(): void
    {
        $training = $this->makeTraining();

        $this->getJson('/api/v1/trainings')->assertUnauthorized();
        $this->getJson('/api/v1/trainings/' . $training->id)->assertUnauthorized();
        $this->postJson('/api/v1/trainings', [])->assertUnauthorized();
        $this->patchJson('/api/v1/trainings/' . $training->id, [])->assertUnauthorized();
    }

    public function test_read_only_key_can_read_but_cannot_write(): void
    {
        $training = $this->makeTraining();
        $token = $this->readOnlyKey();

        $this->withToken($token)->getJson('/api/v1/trainings')->assertOk();
        $this->withToken($token)->getJson('/api/v1/trainings/' . $training->id)->assertOk()->assertJsonPath('data.id', $training->id);
        $this->withToken($token)->postJson('/api/v1/trainings', [])->assertUnauthorized();
        $this->withToken($token)->patchJson('/api/v1/trainings/' . $training->id, [])->assertUnauthorized();
        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/close')->assertUnauthorized();
        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/comments', ['comment' => 'x'])->assertUnauthorized();
    }

    public function test_edit_key_can_list_with_filters_and_pagination(): void
    {
        $active = $this->makeTraining(TrainingStatus::ACTIVE_TRAINING);
        $queued = $this->makeTraining(TrainingStatus::IN_QUEUE, ['type' => 2]);
        $this->makeTraining(TrainingStatus::COMPLETED);
        $token = $this->editKey();

        $this->withToken($token)->getJson('/api/v1/trainings')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 50);

        $this->withToken($token)->getJson('/api/v1/trainings?status=' . TrainingStatus::ACTIVE_TRAINING->value)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);

        $this->withToken($token)->getJson('/api/v1/trainings?type=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $queued->id);

        $this->withToken($token)->getJson('/api/v1/trainings?user_id=' . $active->user_id . '&area_id=' . $active->area_id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($token)->getJson('/api/v1/trainings?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.last_page', 2);

        $this->withToken($token)->getJson('/api/v1/trainings?per_page=101')->assertStatus(422);
        $this->withToken($token)->getJson('/api/v1/trainings?status=99')->assertStatus(422);
    }

    public function test_show_returns_training_with_iso_timestamps(): void
    {
        $training = $this->makeTraining();
        $rating = $this->vatsimRating();
        $training->ratings()->attach($rating->id);

        $response = $this->withToken($this->readOnlyKey())->getJson('/api/v1/trainings/' . $training->id)->assertOk();

        $response->assertJsonPath('data.status', TrainingStatus::ACTIVE_TRAINING->value)
            ->assertJsonPath('data.status_label', 'Active training')
            ->assertJsonPath('data.type_label', 'Standard')
            ->assertJsonPath('data.ratings.0.id', $rating->id)
            ->assertJsonPath('data.paused', false);

        $this->assertSame($training->created_at->toIso8601String(), $response->json('data.created_at'));
    }

    public function test_edit_key_can_create_training_and_second_open_training_is_a_conflict(): void
    {
        Notification::fake();
        $area = Area::factory()->create();
        $student = User::factory()->create();
        $staff = User::factory()->create();
        $rating = $this->vatsimRating();
        $token = $this->editKey();

        $payload = [
            'user_id' => $student->id,
            'area_id' => $area->id,
            'type' => 1,
            'rating_ids' => [$rating->id],
            'actor_cid' => $staff->id,
        ];

        $this->withToken($token)->postJson('/api/v1/trainings', $payload)
            ->assertCreated()
            ->assertJsonPath('data.user_id', $student->id)
            ->assertJsonPath('data.area_id', $area->id)
            ->assertJsonPath('data.status', TrainingStatus::IN_QUEUE->value)
            ->assertJsonPath('data.created_by', $staff->id)
            ->assertJsonPath('data.ratings.0.id', $rating->id);

        $this->assertDatabaseHas('trainings', ['user_id' => $student->id, 'area_id' => $area->id, 'created_by' => $staff->id]);
        Notification::assertSentTo($student, TrainingCreatedNotification::class);

        $this->withToken($token)->postJson('/api/v1/trainings', $payload)
            ->assertStatus(409)
            ->assertJsonStructure(['message']);

        $this->assertSame(1, Training::where('user_id', $student->id)->count());
    }

    public function test_create_accepts_cid_alias_and_validates_input(): void
    {
        Notification::fake();
        $area = Area::factory()->create();
        $student = User::factory()->create();
        $rating = $this->vatsimRating();
        $token = $this->editKey();

        $this->withToken($token)->postJson('/api/v1/trainings', ['area_id' => $area->id, 'type' => 9, 'rating_ids' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'type', 'rating_ids']);

        $this->withToken($token)->postJson('/api/v1/trainings', [
            'cid' => $student->id,
            'area_id' => $area->id,
            'type' => 1,
            'rating_ids' => [$rating->id],
        ])->assertCreated()->assertJsonPath('data.user_id', $student->id);
    }

    public function test_refresh_training_must_cover_active_endorsements(): void
    {
        Notification::fake();
        $area = Area::factory()->create();
        $student = User::factory()->create();
        $covered = $this->facilityRating();
        $missing = $this->facilityRating();
        $area->ratings()->attach([$covered->id, $missing->id]);

        foreach ([$covered, $missing] as $rating) {
            $endorsement = Endorsement::factory()->create([
                'user_id' => $student->id,
                'type' => 'FACILITY',
                'valid_from' => now()->subYear(),
                'expired' => false,
                'revoked' => false,
            ]);
            $endorsement->ratings()->attach($rating->id);
        }

        $this->withToken($this->editKey())->postJson('/api/v1/trainings', [
            'user_id' => $student->id,
            'area_id' => $area->id,
            'type' => 2,
            'rating_ids' => [$covered->id],
        ])->assertStatus(409)->assertJsonPath('message', fn (string $message) => str_contains($message, $missing->name));
    }

    public function test_update_status_mentors_and_pause(): void
    {
        Notification::fake();
        $training = $this->makeTraining(TrainingStatus::IN_QUEUE);
        $mentor = $this->makeMentor($training);
        $expireAt = now()->addMonths(3)->startOfSecond();
        $token = $this->editKey();

        $response = $this->withToken($token)->patchJson('/api/v1/trainings/' . $training->id, [
            'status' => TrainingStatus::ACTIVE_TRAINING->value,
            'mentors' => [['user_id' => $mentor->id, 'expire_at' => $expireAt->toIso8601String()]],
            'paused' => true,
        ])->assertOk();

        $response->assertJsonPath('data.status', TrainingStatus::ACTIVE_TRAINING->value)
            ->assertJsonPath('data.paused', true)
            ->assertJsonPath('data.mentors.0.user_id', $mentor->id);
        $this->assertSame($expireAt->toIso8601String(), $response->json('data.mentors.0.expire_at'));
        $this->assertNotNull($training->fresh()->started_at);
        $this->assertDatabaseHas('training_activity', ['training_id' => $training->id, 'type' => 'MENTOR', 'new_data' => $mentor->id]);
        $this->assertDatabaseHas('training_activity', ['training_id' => $training->id, 'type' => 'PAUSE', 'new_data' => 1]);
        $this->assertDatabaseHas('training_activity', ['training_id' => $training->id, 'type' => 'STATUS', 'new_data' => TrainingStatus::ACTIVE_TRAINING->value]);

        $this->withToken($token)->patchJson('/api/v1/trainings/' . $training->id, ['paused' => false])
            ->assertOk()
            ->assertJsonPath('data.paused', false)
            ->assertJsonPath('data.mentors.0.user_id', $mentor->id);

        $this->withToken($token)->patchJson('/api/v1/trainings/' . $training->id, ['mentors' => []])
            ->assertOk()
            ->assertJsonCount(0, 'data.mentors');
    }

    public function test_update_refuses_mentor_without_permission_and_reopening_over_another_open_training(): void
    {
        Notification::fake();
        $training = $this->makeTraining(TrainingStatus::ACTIVE_TRAINING);
        $regularUser = User::factory()->create();
        $token = $this->editKey();

        $this->withToken($token)->patchJson('/api/v1/trainings/' . $training->id, ['mentors' => [['user_id' => $regularUser->id]]])
            ->assertStatus(409);
        $this->assertCount(0, $training->fresh()->mentors);

        $closed = $this->makeTraining(TrainingStatus::CLOSED_BY_STAFF, ['user_id' => $training->user_id]);

        $this->withToken($token)->patchJson('/api/v1/trainings/' . $closed->id, ['status' => TrainingStatus::IN_QUEUE->value])
            ->assertStatus(409);
        $this->assertSame(TrainingStatus::CLOSED_BY_STAFF, $closed->fresh()->status);

        $this->withToken($token)->patchJson('/api/v1/trainings/' . $training->id, ['status' => TrainingStatus::CLOSED_BY_SYSTEM->value])
            ->assertStatus(422);
    }

    public function test_update_replaces_ratings_and_keeps_completed_parts(): void
    {
        $training = $this->makeTraining();
        $first = $this->vatsimRating();
        $second = $this->vatsimRating();
        $third = $this->vatsimRating();
        $training->ratings()->attach([$first->id, $second->id]);
        $training->ratings()->updateExistingPivot($first->id, ['completed_at' => now()]);

        $this->withToken($this->editKey())->patchJson('/api/v1/trainings/' . $training->id, ['rating_ids' => [$first->id, $third->id]])
            ->assertOk()
            ->assertJsonCount(2, 'data.ratings');

        $ratings = $training->fresh()->ratings;
        $this->assertEqualsCanonicalizing([$first->id, $third->id], $ratings->pluck('id')->all());
        $this->assertNotNull($ratings->firstWhere('id', $first->id)->pivot->completed_at);
        $this->assertNull($ratings->firstWhere('id', $third->id)->pivot->completed_at);
    }

    public function test_close_closes_training_with_reason_and_notifies_the_student(): void
    {
        Notification::fake();
        $training = $this->makeTraining();
        $token = $this->editKey();

        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/close', ['reason' => 'No longer active'])
            ->assertOk()
            ->assertJsonPath('data.status', TrainingStatus::CLOSED_BY_STAFF->value)
            ->assertJsonPath('data.closed_reason', 'No longer active');

        $this->assertNotNull($training->fresh()->closed_at);
        Notification::assertSentTo($training->user, TrainingClosedNotification::class);

        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/close')->assertStatus(409);
    }

    public function test_complete_finishes_a_training_in_progress(): void
    {
        Notification::fake();
        $training = $this->makeTraining();
        $training->ratings()->attach($this->vatsimRating()->id);
        $token = $this->editKey();

        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/complete')
            ->assertOk()
            ->assertJsonPath('data.status', TrainingStatus::COMPLETED->value);

        Notification::assertSentTo($training->user, TrainingClosedNotification::class);

        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/complete')->assertStatus(409);
    }

    public function test_complete_requires_actor_for_facility_ratings_and_grants_the_endorsement(): void
    {
        Notification::fake();
        $training = $this->makeTraining();
        $rating = $this->facilityRating();
        $training->ratings()->attach($rating->id);
        $staff = User::factory()->create();
        $token = $this->editKey();

        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/complete')
            ->assertStatus(422)
            ->assertJsonValidationErrors('actor_cid');

        DivisionApi::shouldReceive('assignTierEndorsement')->once()->withArgs(fn ($user, $r, $requester) => $requester === $staff->id)->andReturn(false);

        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/complete', ['actor_cid' => $staff->id])
            ->assertOk()
            ->assertJsonPath('data.status', TrainingStatus::COMPLETED->value);

        $this->assertDatabaseHas('endorsements', ['user_id' => $training->user_id, 'type' => 'FACILITY']);
        $this->assertDatabaseHas('training_activity', ['training_id' => $training->id, 'type' => 'STATUS', 'triggered_by_id' => $staff->id]);
    }

    public function test_complete_maps_division_api_failure_to_unprocessable(): void
    {
        Notification::fake();
        $training = $this->makeTraining();
        $training->ratings()->attach($this->facilityRating()->id);
        $staff = User::factory()->create();

        DivisionApi::shouldReceive('getName')->andReturn('VATEUD');
        DivisionApi::shouldReceive('assignTierEndorsement')->once()->andReturn(
            new ClientResponse(new GuzzleResponse(422, [], json_encode(['message' => 'nope'])))
        );

        $this->withToken($this->editKey())->postJson('/api/v1/trainings/' . $training->id . '/complete', ['actor_cid' => $staff->id])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_complete_rating_part_signs_off_one_rating_and_rejects_repeats(): void
    {
        Notification::fake();
        $training = $this->makeTraining();
        $first = Rating::factory()->create(['vatsim_rating' => 2]);
        $second = Rating::factory()->create(['vatsim_rating' => 3]);
        $training->ratings()->attach([$first->id, $second->id]);
        $token = $this->editKey();
        $url = '/api/v1/trainings/' . $training->id . '/ratings/' . $first->id . '/complete';

        $response = $this->withToken($token)->postJson($url)->assertOk();
        $completed = collect($response->json('data.ratings'))->firstWhere('id', $first->id);
        $this->assertNotNull($completed['completed_at']);
        $this->assertSame(TrainingStatus::ACTIVE_TRAINING->value, $response->json('data.status'));

        $this->withToken($token)->postJson($url)->assertStatus(409);

        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/ratings/' . $second->id . '/complete')
            ->assertOk()
            ->assertJsonPath('data.status', TrainingStatus::COMPLETED->value);
    }

    public function test_pretraining_toggles_and_sets_the_completed_flag(): void
    {
        $training = $this->makeTraining(TrainingStatus::PRE_TRAINING);
        $token = $this->editKey();
        $url = '/api/v1/trainings/' . $training->id . '/pretraining';

        $this->withToken($token)->postJson($url)->assertOk()->assertJsonPath('data.pre_training_completed', true);
        $this->withToken($token)->postJson($url)->assertOk()->assertJsonPath('data.pre_training_completed', false);
        $this->withToken($token)->postJson($url, ['completed' => true])->assertOk()->assertJsonPath('data.pre_training_completed', true);
        $this->withToken($token)->postJson($url, ['completed' => true])->assertOk()->assertJsonPath('data.pre_training_completed', true);

        $this->assertSame(3, $training->activities()->where('type', 'PRETRAINING')->count());

        $active = $this->makeTraining(TrainingStatus::ACTIVE_TRAINING);
        $this->withToken($token)->postJson('/api/v1/trainings/' . $active->id . '/pretraining')->assertStatus(409);
    }

    public function test_comments_land_on_the_activity_timeline(): void
    {
        $training = $this->makeTraining();
        $actor = User::factory()->create();
        $token = $this->editKey();

        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/comments', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('comment');

        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/comments', ['comment' => 'Needs more practice', 'actor_cid' => $actor->id])
            ->assertCreated()
            ->assertJsonPath('data.type', 'COMMENT')
            ->assertJsonPath('data.comment', 'Needs more practice')
            ->assertJsonPath('data.triggered_by_id', $actor->id);

        $this->withToken($token)->postJson('/api/v1/trainings/' . $training->id . '/comments', ['comment' => 'Anonymous note'])->assertCreated();

        $this->withToken($this->readOnlyKey())->getJson('/api/v1/trainings/' . $training->id . '/activities')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.comment', 'Anonymous note');
    }
}
