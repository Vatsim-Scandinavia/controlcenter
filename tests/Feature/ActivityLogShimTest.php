<?php

namespace Tests\Feature;

use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The legacy ActivityLogService::{debug,info,warning,danger} static methods are
 * kept as thin shims over the activity() helper during the call-site migration.
 */
class ActivityLogShimTest extends TestCase
{
    use RefreshDatabase;

    public function test_info_shim_lowercases_category_into_log_name(): void
    {
        ActivityLogService::info('TRAINING', 'Created training request 5');

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'training',
            'level' => 'info',
            'description' => 'Created training request 5',
        ]);
    }

    public function test_warning_shim_sets_warning_level(): void
    {
        ActivityLogService::warning('ACCESS', 'Logged in with Admin access');

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'access',
            'level' => 'warning',
            'description' => 'Logged in with Admin access',
        ]);
    }

    public function test_danger_shim_sets_danger_level(): void
    {
        ActivityLogService::danger('OTHER', 'Global Settings Updated');

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'other',
            'level' => 'danger',
            'description' => 'Global Settings Updated',
        ]);
    }

    public function test_debug_shim_sets_debug_level(): void
    {
        ActivityLogService::debug('OTHER', 'Some debug detail');

        $this->assertDatabaseHas('activity_logs', [
            'log_name' => 'other',
            'level' => 'debug',
            'description' => 'Some debug detail',
        ]);
    }
}
