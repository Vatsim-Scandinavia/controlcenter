<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\TrainingStatus;
use App\Helpers\VatsimRating;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\Rating;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrainingsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Training $training;

    protected function setUp(): void
    {
        parent::setUp();
        $this->training = Training::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
    }

    #[Test]
    public function student_can_close_their_in_queue_training(): void
    {
        Notification::fake();
        $this->training->update(['status' => TrainingStatus::IN_QUEUE->value]);

        $response = $this->actingAs($this->training->user)
            ->get(route('training.action.close', $this->training));

        $response->assertRedirect();
        $this->training->refresh();
        $this->assertEquals(TrainingStatus::CLOSED_BY_STUDENT, $this->training->status);
    }

    #[Test]
    public function student_cannot_close_active_training(): void
    {
        $this->training->update(['status' => TrainingStatus::ACTIVE_TRAINING->value]);

        $response = $this->actingAs($this->training->user)
            ->get(route('training.action.close', $this->training));

        $response->assertForbidden();
    }

    //    #[Test]
    //    public function user_can_create_a_training_request()
    //    {
    //        $this->withoutExceptionHandling();
    //
    //        $user = factory(\App\Models\User::class)->create();
    //        \Auth::login($user);
    //
    //        $attributes = [
    //            'experience' => $this->faker->numberBetween(1, 5),
    //            'englishOnly' => (int) $this->faker->boolean,
    //            'motivation' => $this->faker->realText(1500,2),
    //            'comment' => "",
    //            'training_level' => \App\Models\Rating::find($this->faker->numberBetween(1,7))->id,
    //            'training_area' => \App\Models\Area::find($this->faker->numberBetween(1,5))->id
    //        ];
    //
    //        $this->assertJson($this->postJson('/training/store', $attributes)->content());
    //        $this->assertDatabaseHas('trainings', ['motivation' => $attributes['motivation']]);
    //    }

    #[Test]
    public function training_page_only_offers_rating_tasks_for_vatsim_rating_trainings()
    {
        $moderator = User::factory()->create();

        $facilityTraining = Training::factory()
            ->has(Rating::factory(['vatsim_rating' => null]))
            ->create(['user_id' => User::factory()->create()->id]);
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $facilityTraining->area->id]);

        $this->actingAs($moderator)->get($facilityTraining->path())
            ->assertSeeText('Custom Request')
            ->assertDontSeeText('Rating Upgrade')
            ->assertDontSeeText('Theoretical Exam Access');

        $combinedTraining = Training::factory()
            ->has(Rating::factory(['vatsim_rating' => VatsimRating::S2, 'name' => 'TST-S2']))
            ->create(['user_id' => User::factory()->create()->id, 'area_id' => $facilityTraining->area_id]);
        $combinedTraining->ratings()->save(Rating::factory()->create(['vatsim_rating' => null, 'name' => 'TST-MAE']));

        $this->actingAs($moderator)->get($combinedTraining->path())
            ->assertSeeText('Rating Upgrade')
            ->assertSeeText('Theoretical Exam Access')
            ->assertSee('for <b>TST-S2</b> rating', false);
    }

    #[Test]
    public function get_highest_vatsim_rating_returns_rating_with_highest_vatsim_rating(): void
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        // Attach two ratings with different VATSIM ratings to the training
        $ratingS1 = Rating::factory()->create(['vatsim_rating' => VatsimRating::S1->value]);
        $ratingS2 = Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value]);

        $training->ratings()->attach([$ratingS1->id, $ratingS2->id]);
        $training->load('ratings'); // Reload relation

        $highest = $training->getHighestVatsimRating();

        $this->assertNotNull($highest);
        $this->assertEquals(VatsimRating::S2, $highest->vatsim_rating);
    }

    #[Test]
    public function guest_cant_create_training_request()
    {
        $attributes = [
            'experience' => $this->faker->numberBetween(1, 5),
            'englishOnly' => (int) $this->faker->boolean,
            'motivation' => $this->faker->realText(1500, 2),
            'comment' => '',
            'training_level' => Rating::find($this->faker->numberBetween(1, 7))->id,
            'training_area' => Area::find($this->faker->numberBetween(1, 5))->id,
        ];

        $response = $this->post('/training/store', $attributes);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function test_director_is_eligible_as_training_mentor_in_their_area(): void
    {
        $area = Area::factory()->create();
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => $area->id]);

        $this->assertTrue($director->hasPermission('training.mentor', $area));
        $this->assertTrue($director->hasPermission('training.mentor-dashboard.view'));
    }

    #[Test]
    public function moderator_can_update_training_request()
    {
        $moderator = User::factory()->create();

        $training = Training::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);

        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $training->area->id]);

        $this->assertDatabaseHas('trainings', ['id' => $training->id]);

        $this->actingAs($moderator)
            ->patch($training->path(), $attributes = ['status' => 0])
            ->assertRedirect($training->path())
            ->assertSessionHas('success', 'Training successfully updated');

        $this->assertDatabaseHas('trainings', ['id' => $training->id, 'status' => $attributes['status']]);
    }

    #[Test]
    public function a_regular_user_cant_update_a_training()
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);
        $user = $training->user;
        $user->roleAssignments()->create(['role' => 'mentor', 'area_id' => $training->area->id]);

        $this->assertDatabaseHas('trainings', ['id' => $training->id]);

        $this->actingAs($user)
            ->patch($training->path(), $attributes = ['status' => 0])
            ->assertStatus(403);
    }

    // #[Test]
    public function moderator_can_update_the_trainings_status()
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 0]);

        $this->assertDatabaseHas('trainings', ['id' => $training->id, 'status' => 0]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 1]);

        $this->assertDatabaseHas('trainings', ['id' => $training->id, 'status' => 1, 'started_at' => $training->fresh()->started_at->format('Y-m-d H:i:s')]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 3]);

        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'status' => 3,
            'started_at' => $training->fresh()->started_at->format('Y-m-d H:i:s'),
            'closed_at' => $training->fresh()->closed_at->format('Y-m-d H:i:s'),
        ]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 0]);

        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'status' => 0,
            'started_at' => null,
            'closed_at' => null,
        ]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => -1]);

        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'status' => -1,
            'started_at' => null,
            'closed_at' => null,
        ]);
    }

    //    #[Test]
    //    public function a_mentor_can_be_added()
    //    {
    //        $training = factory(\App\Models\Training::class)->create();
    //        $moderator = factory(\App\Models\User::class)->create(['group' => 2]);
    //        $mentor = factory(\App\Models\User::class)->create(['group' => 3]);
    //
    //        $training->area->mentors()->attach($mentor);
    //
    //        $this->actingAs($moderator)
    //            ->patchJson(route('training.update', ['training' => $training]), ['mentors' => [$mentor->id]])
    //            ->assertStatus(302);
    //
    //        $this->assertTrue($training->mentors->contains($mentor));
    //    }

    //    #[Test]
    //    public function a_training_can_have_many_mentors_added()
    //    {
    //        $training = factory(\App\Models\Training::class)->create();
    //        $moderator = factory(\App\Models\User::class)->create(['group' => 2]);
    //
    //        $attributes = [
    //            'mentors' => [
    //                factory(\App\Models\User::class)->create(['group' => 3])->id,
    //                factory(\App\Models\User::class)->create(['group' => 3])->id
    //            ]
    //        ];
    //
    //        $training->area->mentors()->attach($attributes['mentors']);
    //
    //        $this->actingAs($moderator)
    //                ->patchJson(route('training.update', ['training' => $training]), $attributes)
    //                ->assertStatus(302);
    //
    //        $this->assertTrue($training->mentors->contains($attributes['mentors'][0]));
    //        $this->assertTrue($training->mentors->contains($attributes['mentors'][1]));
    //
    //    }

    #[Test]
    public function test_director_can_create_training_requests_for_others(): void
    {
        $director = User::factory()->create();
        $director->roleAssignments()->create(['role' => 'director', 'area_id' => null]);

        $this->assertTrue($director->can('create', Training::class));
        $this->assertTrue($director->hasPermission('training.activities.view', Area::factory()->create()));
    }

    #[Test]
    public function a_mentor_cant_be_added_if_they_are_not_a_mentor_in_the_right_area()
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
            'area_id' => 1,
        ]);
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $training->area->id]);
        $mentor = User::factory()->create();

        $mentor->roleAssignments()->create(['role' => 'mentor', 'area_id' => 2]);

        $this->actingAs($moderator)
            ->patchJson(route('training.update.details', ['training' => $training]), ['mentors' => [$mentor->id]])
            ->assertStatus(302);

        $this->assertNotTrue($training->mentors->contains($mentor));
    }

    #[Test]
    public function obs_user_gets_zero_vatsim_hours_on_api_404(): void
    {
        Http::fake([
            'api.vatsim.net/*' => Http::response([], 404),
        ]);

        Setting::set('trainingEnabled', true);
        Setting::set('trainingSubDivisions', 'SCA');
        Setting::set('atcActivityBasedOnTotalHours', false);

        $obsUser = User::factory()->create([
            'rating' => VatsimRating::OBS->value,
            'division' => config('app.owner_code'),
            'subdivision' => 'SCA',
        ]);

        $response = $this->actingAs($obsUser)->get(route('training.apply'));

        // With the fix, the page loads without an error redirect
        $response->assertSuccessful();
    }

    #[Test]
    public function apply_page_shows_available_ratings_for_s1_user(): void
    {
        Http::fake([
            'api.vatsim.net/*' => Http::response(['s1' => 10], 200),
        ]);

        Setting::set('trainingEnabled', true);
        Setting::set('trainingSubDivisions', 'SCA');
        Setting::set('atcActivityBasedOnTotalHours', false);

        $area = Area::factory()->create();
        $rating = Rating::factory()->create(['vatsim_rating' => VatsimRating::S2->value]);

        // Attach to area with required_vatsim_rating = S1 (value 2) so an S1 user qualifies
        $area->ratings()->attach($rating->id, [
            'required_vatsim_rating' => VatsimRating::S1->value,
            'allow_bundling' => false,
            'hour_requirement' => 0,
            'queue_length_low' => 0,
            'queue_length_high' => 0,
        ]);

        $s1User = User::factory()->create([
            'rating' => VatsimRating::S1->value,
            'division' => config('app.owner_code'),
            'subdivision' => 'SCA',
        ]);

        // S1 user needs to be ATC active to pass TrainingPolicy (S1 > OBS triggers the check)
        AtcActivity::create([
            'user_id' => $s1User->id,
            'area_id' => $area->id,
            'atc_active' => true,
            'hours' => 0,
            'hours_in_period' => 0,
        ]);

        $response = $this->actingAs($s1User)->get(route('training.apply'));

        $response->assertSuccessful();
        // The S2 rating should appear as available for the S1 user
        $response->assertSee($rating->name);
    }
}
