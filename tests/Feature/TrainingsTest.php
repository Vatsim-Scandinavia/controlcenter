<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TrainingsTest extends TestCase
{
    use WithFaker, RefreshDatabase;

//    /** @test */
//    public function user_can_create_a_training_request()
//    {
//        $this->withoutExceptionHandling();
//
//        $user = factory(\App\User::class)->create();
//        \Auth::login($user);
//
//        $attributes = [
//            'experience' => $this->faker->numberBetween(1, 5),
//            'englishOnly' => (int) $this->faker->boolean,
//            'motivation' => $this->faker->realText(1500,2),
//            'comment' => "",
//            'training_level' => \App\Rating::find($this->faker->numberBetween(1,7))->id,
//            'training_country' => \App\Country::find($this->faker->numberBetween(1,5))->id
//        ];
//
//        $this->assertJson($this->postJson('/training/store', $attributes)->content());
//        $this->assertDatabaseHas('trainings', ['motivation' => $attributes['motivation']]);
//    }

    /** @test */
    public function guest_cant_create_training_request()
    {
        $attributes = [
            'experience' => $this->faker->numberBetween(1, 5),
            'englishOnly' => (int) $this->faker->boolean,
            'motivation' => $this->faker->realText(1500,2),
            'comment' => "",
            'training_level' => \App\Rating::find($this->faker->numberBetween(1,7))->id,
            'training_country' => \App\Country::find($this->faker->numberBetween(1,5))->id
        ];

        $response = $this->post('/training/store', $attributes);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function moderator_can_update_training_request()
    {

        $moderator = factory(\App\User::class)->create();
        $moderator->group = 2;
        $moderator->save();

        $training = factory(\App\Training::class)->create();

        $this->assertDatabaseHas('trainings', ['id' => $training->id]);

        $this->actingAs($moderator)
            ->patch($training->path(), $attributes = ['status' => 0])
            ->assertRedirect($training->path())
            ->assertSessionHas('success', 'Training successfully updated');

        $this->assertDatabaseHas('trainings', ['id' => $training->id, 'status' => $attributes['status']]);

    }

    /** @test */
    public function a_regular_user_cant_update_a_training()
    {

        $training = factory(\App\Training::class)->create();
        $user = $training->user;

        $this->assertDatabaseHas('trainings', ['id' => $training->id]);

        $user->group = 3;
        $user->save();

        $this->actingAs($user)
            ->patch($training->path(), $attributes = ['status' => 0])
            ->assertStatus(403);

    }


//    /** @test */
    public function moderator_can_update_the_trainings_status()
    {
        $training = factory(\App\Training::class)->create();
        $moderator = factory(\App\User::class)->create();
        $moderator->update(['group' => 1]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 0]);

        $this->assertDatabaseHas('trainings', ['id' => $training->id, 'status' => 0]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 1]);

        $this->assertDatabaseHas('trainings', ['id' => $training->id, 'status' => 1, 'started_at' => $training->fresh()->started_at->format('Y-m-d H:i:s')]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 3]);

        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'status' => 3,
            'started_at' => $training->fresh()->started_at->format('Y-m-d H:i:s'),
            'closed_at' => $training->fresh()->closed_at->format('Y-m-d H:i:s')
        ]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => 0]);

        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'status' => 0,
            'started_at' => null,
            'closed_at' => null
        ]);

        $this->actingAs($moderator)->patch(route('training.update', ['training' => $training->id]), ['status' => -1]);

        $this->assertDatabaseHas('trainings', [
            'id' => $training->id,
            'status' => -1,
            'started_at' => null,
            'closed_at' => null
        ]);

    }

//    /** @test */
//    public function a_mentor_can_be_added()
//    {
//        $training = factory(\App\Training::class)->create();
//        $moderator = factory(\App\User::class)->create(['group' => 2]);
//        $mentor = factory(\App\User::class)->create(['group' => 3]);
//
//        $training->country->mentors()->attach($mentor);
//
//        $this->actingAs($moderator)
//            ->patchJson(route('training.update', ['training' => $training]), ['mentors' => [$mentor->id]])
//            ->assertStatus(302);
//
//        $this->assertTrue($training->mentors->contains($mentor));
//    }

//    /** @test */
//    public function a_training_can_have_many_mentors_added()
//    {
//        $training = factory(\App\Training::class)->create();
//        $moderator = factory(\App\User::class)->create(['group' => 2]);
//
//        $attributes = [
//            'mentors' => [
//                factory(\App\User::class)->create(['group' => 3])->id,
//                factory(\App\User::class)->create(['group' => 3])->id
//            ]
//        ];
//
//        $training->country->mentors()->attach($attributes['mentors']);
//
//        $this->actingAs($moderator)
//                ->patchJson(route('training.update', ['training' => $training]), $attributes)
//                ->assertStatus(302);
//
//        $this->assertTrue($training->mentors->contains($attributes['mentors'][0]));
//        $this->assertTrue($training->mentors->contains($attributes['mentors'][1]));
//
//    }

    /** @test */
    public function a_mentor_cant_be_added_if_they_are_not_a_mentor_in_the_right_country()
    {
        $training = factory(\App\Training::class)->create();
        $moderator = factory(\App\User::class)->create(['group' => 2]);
        $mentor = factory(\App\User::class)->create(['group' => 3]);

        $this->actingAs($moderator)
            ->patchJson(route('training.update', ['training' => $training]), ['mentors' => [$mentor->id]])
            ->assertStatus(302);

        $this->assertNotTrue($training->mentors->contains($mentor));
    }

}
