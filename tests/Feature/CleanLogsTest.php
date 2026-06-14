<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
