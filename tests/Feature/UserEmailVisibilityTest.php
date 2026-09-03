<?php

namespace Tests\Feature;

use App\Facades\DivisionApi;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmailVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DivisionApi::shouldReceive('getUserExams')->andReturn(null);
    }

    public function test_email_is_not_in_profile_html_before_reveal(): void
    {
        $viewer = User::factory()->create();
        $viewer->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);
        $target = User::factory()->create(['email' => 'private@example.test']);

        $this->actingAs($viewer)
            ->get(route('user.show', $target))
            ->assertOk()
            ->assertDontSee('private@example.test')
            ->assertSee('Reveal email');
    }

    public function test_profile_viewer_without_permission_cannot_reveal_email(): void
    {
        $target = User::factory()->create(['email' => 'private@example.test']);

        $this->actingAs($target)
            ->get(route('user.show', $target))
            ->assertOk()
            ->assertDontSee('private@example.test')
            ->assertDontSee('Reveal email')
            ->assertSee('Hidden');

        $this->actingAs($target)
            ->postJson(route('user.email.reveal', $target), ['reason' => 'Checking my details'])
            ->assertForbidden();
    }

    public function test_authorized_reveal_returns_email_and_records_audit_log(): void
    {
        $viewer = User::factory()->create();
        $viewer->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);
        $target = User::factory()->create(['email' => 'private@example.test']);

        $this->actingAs($viewer)
            ->postJson(route('user.email.reveal', $target), [
                'reason' => 'Contacting the student about an upcoming session',
            ])
            ->assertOk()
            ->assertExactJson(['email' => 'private@example.test']);

        $log = ActivityLog::query()
            ->where('log_name', 'user-email')
            ->where('event', 'viewed')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Email address viewed', $log->description);
        $this->assertSame($viewer->id, $log->causer_id);
        $this->assertSame($target->id, $log->subject_id);
        $this->assertSame(User::class, $log->subject_type);
        $this->assertSame('Contacting the student about an upcoming session', $log->properties['reason']);
        $this->assertStringNotContainsString($target->email, $log->properties->toJson());
    }

    public function test_reveal_requires_a_meaningful_reason(): void
    {
        $viewer = User::factory()->create();
        $viewer->roleAssignments()->create(['role' => 'admin', 'area_id' => null]);
        $target = User::factory()->create();

        $this->actingAs($viewer)
            ->postJson(route('user.email.reveal', $target), ['reason' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->assertDatabaseMissing('activity_logs', [
            'log_name' => 'user-email',
            'event' => 'viewed',
        ]);
    }
}
