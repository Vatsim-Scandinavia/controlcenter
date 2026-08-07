<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeedbackActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function feedbackLogs()
    {
        return ActivityLog::where('log_name', 'feedback');
    }

    #[Test]
    public function creating_feedback_writes_no_feedback_activity_row(): void
    {
        Feedback::factory()->create();

        $this->assertSame(0, $this->feedbackLogs()->count());
    }

    #[Test]
    public function updating_a_reference_writes_one_feedback_activity_row_with_causer_and_changes(): void
    {
        $feedback = Feedback::factory()->create();
        $newController = User::factory()->create();
        $causer = User::factory()->create();
        $this->actingAs($causer);

        $feedback->update(['reference_user_id' => $newController->id]);

        $this->assertSame(1, $this->feedbackLogs()->count());

        $log = $this->feedbackLogs()->first();
        $this->assertEquals($causer->id, $log->causer_id);
        $this->assertEquals($newController->id, data_get($log->attribute_changes, 'attributes.reference_user_id'));
    }

    #[Test]
    public function updating_with_no_effective_change_writes_no_feedback_activity_row(): void
    {
        $feedback = Feedback::factory()->create();

        $feedback->update(['reference_user_id' => $feedback->reference_user_id]);

        $this->assertSame(0, $this->feedbackLogs()->count());
    }
}
