<?php

namespace Tests\Feature;

use App\Helpers\LogName;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LogName is the set of categories an activity entry can be filed under. The
 * enum is passed straight to activity(), which stores the backing value.
 */
class LogNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_case_is_stored_as_its_backing_value(): void
    {
        foreach (LogName::cases() as $logName) {
            activity($logName)->log('Entry for ' . $logName->name);

            $this->assertDatabaseHas('activity_logs', [
                'log_name' => $logName->value,
                'description' => 'Entry for ' . $logName->name,
            ]);
        }
    }

    public function test_cases_can_be_queried_with_the_in_log_scope(): void
    {
        activity(LogName::Access)->log('Logged in');
        activity(LogName::Booking)->log('Created booking');
        activity(LogName::Training)->log('Created training request');

        $logs = ActivityLog::inLog(LogName::Access, LogName::Booking)->get();

        $this->assertEqualsCanonicalizing(
            ['Logged in', 'Created booking'],
            $logs->pluck('description')->all(),
        );
    }

    public function test_backing_values_are_lowercase_and_unique(): void
    {
        $values = array_column(LogName::cases(), 'value');

        $this->assertSame(array_map(strtolower(...), $values), $values);
        $this->assertSame(array_unique($values), $values);
    }
}
