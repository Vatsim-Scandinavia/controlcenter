<?php

namespace Tests\Unit;

use App\Exceptions\PolicyMissingException;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserUnitTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['id' => 10000000]);
    }

    #[Test]
    public function user_can_have_a_first_name()
    {
        $name = $this->faker->firstName;
        $this->user->first_name = $name;

        $this->assertEquals($name, $this->user->first_name);
    }

    #[Test]
    public function user_can_have_a_last_name()
    {
        $name = $this->faker->lastName;
        $this->user->last_name = $name;

        $this->assertEquals($name, $this->user->last_name);
    }

    #[Test]
    public function user_can_have_full_name()
    {
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $name = $firstName . ' ' . $lastName;

        $user = $this->user;
        $user->first_name = $firstName;
        $user->last_name = $lastName;

        $this->assertEquals($name, $this->user->name);
    }

    #[Test]
    public function user_can_have_trainings_they_can_access()
    {
        $training = Training::factory()->create(['user_id' => $this->user->id]);

        $this->user->can('view', $training)
            ? $this->assertTrue($this->user->viewableModels('\App\Models\Training')->contains($training))
            : $this->assertFalse($this->user->viewableModels('\App\Models\Training')->contains($training));
    }

    #[Test]
    public function trainings_can_exist_with_out_user_being_able_to_see_them()
    {
        $otherUser = User::factory()->create(['id' => ($this->user->id + 1)]);
        $training = Training::factory()->create(['user_id' => $otherUser->id]);

        $this->user->can('view', $training)
            ? $this->assertTrue($this->user->viewableModels('\App\Models\Training')->contains($training))
            : $this->assertFalse($this->user->viewableModels('\App\Models\Training')->contains($training));
    }

    #[Test]
    public function an_exception_is_thrown_if_a_policy_does_not_exist_for_class()
    {
        $this->expectException(PolicyMissingException::class);

        $this->user->viewableModels('\App\Test');
    }
}
