<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Handover;
use App\Models\Training;
use App\Exceptions\PolicyMissingException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserUnitTest extends TestCase
{

    use WithFaker, RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['id' => 10000000]);
        $this->user->handover = Handover::factory()->make(['id' => 10000000]);
    }

    /** @test */
    public function user_can_have_a_first_name()
    {
        $name = $this->faker->firstName;
        $this->user->handover->first_name = $name;

        $this->assertEquals($name, $this->user->first_name);
    }

    /** @test */
    public function user_can_have_a_last_name()
    {
        $name = $this->faker->lastName;
        $this->user->handover->last_name = $name;

        $this->assertEquals($name, $this->user->last_name);
    }

    /** @test */
    public function user_can_have_full_name()
    {
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $name = $firstName . " " . $lastName;

        $handover = $this->user->handover;
        $handover->first_name = $firstName;
        $handover->last_name = $lastName;

        $this->assertEquals($name, $this->user->name);
    }

    /** @test */
    public function user_can_have_trainings_they_can_access()
    {
        $training = Training::factory()->create(['user_id' => $this->user->id]);

        $this->user->can('view', $training)
            ? $this->assertTrue($this->user->viewableModels('\App\Models\Training')->contains($training))
            : $this->assertFalse($this->user->viewableModels('\App\Models\Training')->contains($training));

    }

    /** @test */
    public function trainings_can_exist_with_out_user_being_able_to_see_them()
    {
        $otherUser = User::factory()->create(['id' => ($this->user->id + 1)]);
        $training = Training::factory()->create(['user_id' => $otherUser->id]);

        $this->user->can('view', $training)
            ? $this->assertTrue($this->user->viewableModels('\App\Models\Training')->contains($training))
            : $this->assertFalse($this->user->viewableModels('\App\Models\Training')->contains($training));
    }

    /** @test */
    public function an_exception_is_thrown_if_a_policy_does_not_exist_for_class()
    {
        $this->expectException(PolicyMissingException::class);

        $this->user->viewableModels('\App\Test');
    }


}
