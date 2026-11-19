<?php

namespace Tests\Feature;

use App\Console\Kernel;
use App\Models\ActivityLog;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class CleanLogsTest extends TestCase
{
    use RefreshDatabase;

    private function logAgedDays(int $days, array $attributes = []): ActivityLog
    {
        $log = ActivityLog::create(array_merge([
            'log_name' => 'access',
            'description' => 'Logged in',
            'ip_address' => '203.0.113.7',
            'user_agent' => 'TestBrowser/2.0',
        ], $attributes));

        $log->forceFill(['created_at' => now()->subDays($days)])->saveQuietly();

        return $log->fresh();
    }

    public function test_clean_logs_scrubs_ip_and_user_agent_older_than_two_weeks(): void
    {
        $old = $this->logAgedDays(21);

        $this->artisan('clean:logs')->assertSuccessful();

        $old->refresh();
        $this->assertNull($old->ip_address);
        $this->assertNull($old->user_agent);
    }

    public function test_clean_logs_keeps_recent_request_context(): void
    {
        $recent = $this->logAgedDays(7);

        $this->artisan('clean:logs')->assertSuccessful();

        $recent->refresh();
        $this->assertSame('203.0.113.7', $recent->ip_address);
        $this->assertSame('TestBrowser/2.0', $recent->user_agent);
    }

    public function test_clean_logs_does_not_delete_entries(): void
    {
        $this->logAgedDays(200);

        $this->artisan('clean:logs')->assertSuccessful();

        $this->assertSame(1, ActivityLog::count());
    }

    public function test_activitylog_clean_deletes_entries_older_than_retention(): void
    {
        $this->logAgedDays(120);
        $kept = $this->logAgedDays(30);

        $this->artisan('activitylog:clean')->assertSuccessful();

        $this->assertSame(1, ActivityLog::count());
        $this->assertTrue(ActivityLog::whereKey($kept->id)->exists());
    }

    public function test_activitylog_clean_runs_unattended_in_production(): void
    {
        $this->logAgedDays(120);
        $kept = $this->logAgedDays(30);

        $this->app['env'] = 'production';

        $this->artisan('activitylog:clean', ['--force' => true])->assertSuccessful();

        $this->assertSame(1, ActivityLog::count());
        $this->assertTrue(ActivityLog::whereKey($kept->id)->exists());
    }

    public function test_activitylog_clean_is_scheduled_with_force(): void
    {
        $schedule = new Schedule;
        $kernel = $this->app->make(Kernel::class);
        (new ReflectionMethod($kernel, 'schedule'))->invoke($kernel, $schedule);

        $events = collect($schedule->events())
            ->filter(fn (Event $event) => str_contains((string) $event->command, 'activitylog:clean'));

        $this->assertCount(1, $events);
        $this->assertMatchesRegularExpression('/\sactivitylog:clean(\s.*)?\s--force(\s|$)/', $events->first()->command);
    }
}
