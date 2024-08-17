<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TrainingsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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
    public function guest_cant_create_training_request()
    {
        $attributes = [
            'experience' => $this->faker->numberBetween(1, 5),
            'englishOnly' => (int) $this->faker->boolean,
            'motivation' => $this->faker->realText(1500, 2),
            'comment' => '',
            'training_level' => \App\Models\Rating::find($this->faker->numberBetween(1, 7))->id,
            'training_area' => \App\Models\Area::find($this->faker->numberBetween(1, 5))->id,
        ];

        $response = $this->post('/training/store', $attributes);
        $response->assertRedirect('/login');
    }

    #[Test]
    public function moderator_can_update_training_request()
    {
        $moderator = User::factory()->create();

        $training = Training::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);

        $moderator->groups()->attach(2, ['area_id' => $training->area->id]);

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
        $user->groups()->attach(3, ['area_id' => $training->area->id]);

        $this->assertDatabaseHas('trainings', ['id' => $training->id]);

        $this->actingAs($user)
            ->patch($training->path(), $attributes = ['status' => 0])
            ->assertStatus(403);
    }

    //#[Test]
    public function moderator_can_update_the_trainings_status()
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);
        $moderator = User::factory()->create();
        $moderator->groups()->attach(1, ['area_id' => $training->area->id]);

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
    public function a_mentor_cant_be_added_if_they_are_not_a_mentor_in_the_right_area()
    {
        $training = Training::factory()->create([
            'user_id' => User::factory()->create(['id' => 10000005])->id,
        ]);
        $moderator = User::factory()->create();
        $moderator->groups()->attach(2, ['area_id' => $training->area->id]);
        $mentor = User::factory()->create();

        // We hardcoded area id to 1 in the factory so anything other than 1 will work - let's pick 2 lol.
        $mentor->groups()->attach(3, ['area_id' => 2]);

        $this->actingAs($moderator)
            ->patchJson(route('training.update.details', ['training' => $training]), ['mentors' => [$mentor->id]])
            ->assertStatus(302);

        $this->assertNotTrue($training->mentors->contains($mentor));
    }
}
