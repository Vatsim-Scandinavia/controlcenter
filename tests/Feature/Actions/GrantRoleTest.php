<?php

namespace Tests\Feature\Actions;

use App\Actions\GrantRole;
use App\Facades\DivisionApi;
use App\Models\Area;
use App\Models\User;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GrantRoleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        return $admin;
    }

    #[Test]
    public function it_creates_an_area_assignment(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $area = Area::factory()->create();

        $error = (new GrantRole)($actor, $target, 'moderator', $area);

        $this->assertNull($error);
        $this->assertDatabaseHas('role_user', [
            'user_id' => $target->id, 'role' => 'moderator', 'area_id' => $area->id,
        ]);
    }

    #[Test]
    public function it_is_idempotent_when_the_assignment_already_exists(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $target->roleAssignments()->create(['role' => 'staff', 'area_id' => null]);

        $error = (new GrantRole)($actor, $target, 'staff', null);

        $this->assertNull($error);
        $this->assertSame(1, $target->roleAssignments()->where('role', 'staff')->count());
    }

    #[Test]
    public function it_throws_when_actor_is_not_authorized(): void
    {
        $actor = User::factory()->create(); // no users.manage
        $target = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        (new GrantRole)($actor, $target, 'staff', null);
    }

    #[Test]
    public function it_creates_a_mentor_assignment_when_the_division_api_reports_success(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $area = Area::factory()->create();

        DivisionApi::shouldReceive('assignMentor')->once()->andReturn(false);

        $error = (new GrantRole)($actor, $target, 'mentor', $area);

        $this->assertNull($error);
        $this->assertDatabaseHas('role_user', [
            'user_id' => $target->id, 'role' => 'mentor', 'area_id' => $area->id,
        ]);
    }

    #[Test]
    public function it_calls_the_division_api_before_creating_a_mentor_and_blocks_on_failure(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $area = Area::factory()->create();

        $failed = new ClientResponse(new GuzzleResponse(422, [], json_encode(['message' => 'nope'])));
        DivisionApi::shouldReceive('assignMentor')->once()->andReturn($failed);
        DivisionApi::shouldReceive('getName')->andReturn('VATEUD');

        $error = (new GrantRole)($actor, $target, 'mentor', $area);

        $this->assertStringContainsString('nope', $error);
        $this->assertDatabaseMissing('role_user', ['user_id' => $target->id, 'role' => 'mentor']);
    }
}
