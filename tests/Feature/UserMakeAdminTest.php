<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserMakeAdminTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_command_assigns_global_admin(): void
    {
        $user = User::factory()->create();

        $this->artisan('user:makeadmin')
            ->expectsQuestion("What is the user's CID?", $user->id)
            ->assertExitCode(0);

        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role' => 'admin',
            'area_id' => null,
        ]);
    }

    #[Test]
    public function test_command_is_idempotent(): void
    {
        $user = User::factory()->create();
        $user->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);

        $this->artisan('user:makeadmin')
            ->expectsQuestion("What is the user's CID?", $user->id)
            ->assertExitCode(0);

        $this->assertEquals(1, $user->roleAssignments()->where('role', 'admin')->count());
    }

    #[Test]
    public function test_command_fails_for_unknown_cid(): void
    {
        $this->artisan('user:makeadmin')
            ->expectsQuestion("What is the user's CID?", 999999999)
            ->assertExitCode(1);
    }
}
