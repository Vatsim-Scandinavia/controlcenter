<?php

namespace Tests\Feature;

use App\Helpers\ActivityLevel;
use App\Models\ActivityLog;
use App\Models\Endorsement;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ActivityLogModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_defaults_to_info_and_is_cast_to_enum(): void
    {
        $log = ActivityLog::create(['log_name' => 'other', 'description' => 'No level given']);

        $this->assertSame(ActivityLevel::Info, $log->fresh()->level);
    }

    public function test_level_round_trips_through_the_database(): void
    {
        $log = ActivityLog::create([
            'log_name' => 'other',
            'description' => 'Dangerous thing',
            'level' => ActivityLevel::Danger,
        ]);

        $this->assertSame(ActivityLevel::Danger, $log->fresh()->level);
    }

    public function test_request_context_is_not_captured_when_running_in_console(): void
    {
        // The test process runs in console context by default.
        $log = ActivityLog::create(['log_name' => 'other', 'description' => 'System action']);

        $this->assertNull($log->ip_address);
        $this->assertNull($log->user_agent);
    }

    public function test_ip_and_user_agent_are_captured_during_an_http_request(): void
    {
        $this->app->instance('request', Request::create('/whatever', 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_USER_AGENT' => 'TestBrowser/2.0',
        ]));
        (function () {
            $this->isRunningInConsole = false;
        })->call($this->app);

        $log = ActivityLog::create(['log_name' => 'access', 'description' => 'Logged in']);

        $this->assertSame('203.0.113.7', $log->ip_address);
        $this->assertSame('TestBrowser/2.0', $log->user_agent);
    }

    public function test_subject_route_links_known_subject_types(): void
    {
        $trainingLog = ActivityLog::create([
            'description' => 'Training created',
            'subject_type' => Training::class,
            'subject_id' => 42,
        ]);

        $userLog = ActivityLog::create([
            'description' => 'User updated',
            'subject_type' => User::class,
            'subject_id' => 7,
        ]);

        $this->assertSame(route('training.show', 42), $trainingLog->subject_route);
        $this->assertSame(route('user.show', 7), $userLog->subject_route);
    }

    public function test_subject_route_is_null_without_a_linkable_subject(): void
    {
        $withoutSubject = ActivityLog::create(['description' => 'Settings updated']);

        // A subject type that has no web show route falls back to no link.
        $unlinkable = ActivityLog::create([
            'description' => 'Endorsement created',
            'subject_type' => Endorsement::class,
            'subject_id' => 1,
        ]);

        $this->assertNull($withoutSubject->subject_route);
        $this->assertNull($unlinkable->subject_route);
    }
}
