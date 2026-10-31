<?php

namespace Tests\Feature\Events;

use App\Events\V1\EndorsementGranted;
use App\Events\V1\EndorsementRevoked;
use App\Events\V1\FeedbackSubmitted;
use App\Events\V1\RoleGranted;
use App\Events\V1\RoleRevoked;
use App\Events\V1\TaskCreated;
use App\Events\V1\TaskStatusChanged;
use App\Events\V1\TrainingCreated;
use App\Events\V1\TrainingStatusChanged;
use App\Events\V1\VoteClosed;
use App\Events\V1\VoteCreated;
use App\Helpers\TaskStatus;
use App\Helpers\TrainingStatus;
use App\Models\Endorsement;
use App\Models\Feedback;
use App\Models\RoleAssignment;
use App\Models\Task;
use App\Models\Training;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verifies the versioned domain events fire at their authoritative Eloquent
 * mutation points. A real wildcard listener captures them because model-level
 * events (and therefore the observers that fire these) do not run under
 * Event::fake().
 *
 * @var array<int, string>
 */
class DomainEventsTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $fired = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->fired = [];

        Event::listen('*', function (string $event): void {
            if (str_starts_with($event, 'App\\Events\\V1\\')) {
                $this->fired[] = $event;
            }
        });
    }

    private function assertFired(string $eventClass): void
    {
        $this->assertContains($eventClass, $this->fired, "Expected domain event [{$eventClass}] to fire.");
    }

    private function assertNotFired(string $eventClass): void
    {
        $this->assertNotContains($eventClass, $this->fired, "Did not expect domain event [{$eventClass}] to fire.");
    }

    #[Test]
    public function training_creation_and_status_change_fire_events(): void
    {
        $user = User::factory()->create();

        $training = Training::factory()->create([
            'user_id' => $user->id,
            'status' => TrainingStatus::IN_QUEUE,
            'area_id' => 1,
        ]);

        $this->assertFired(TrainingCreated::class);
        $this->assertNotFired(TrainingStatusChanged::class);

        $training->updateStatus(TrainingStatus::PRE_TRAINING);

        $this->assertFired(TrainingStatusChanged::class);
    }

    #[Test]
    public function endorsement_grant_and_revoke_fire_events(): void
    {
        User::factory()->create();

        $endorsement = Endorsement::factory()->create();
        $this->assertFired(EndorsementGranted::class);
        $this->assertNotFired(EndorsementRevoked::class);

        // Endorsement is guarded; production revokes via direct assignment.
        $endorsement->revoked = true;
        $endorsement->save();
        $this->assertFired(EndorsementRevoked::class);
    }

    #[Test]
    public function role_grant_and_revoke_fire_events(): void
    {
        $user = User::factory()->create();

        $assignment = RoleAssignment::create([
            'user_id' => $user->id,
            'role' => 'admin',
            'area_id' => null,
        ]);
        $this->assertFired(RoleGranted::class);

        $assignment->delete();
        $this->assertFired(RoleRevoked::class);
    }

    #[Test]
    public function vote_creation_and_scheduled_close_fire_events(): void
    {
        $vote = new Vote;
        $vote->question = 'Should we test events?';
        $vote->require_active = false;
        $vote->closed = false;
        $vote->end_at = now()->subMinute();
        $vote->save();

        $this->assertFired(VoteCreated::class);
        $this->assertNotFired(VoteClosed::class);

        Artisan::call('clean:votes');

        $this->assertFired(VoteClosed::class);
        $this->assertTrue($vote->fresh()->closed);
    }

    #[Test]
    public function feedback_submission_fires_event(): void
    {
        Feedback::factory()->create();

        $this->assertFired(FeedbackSubmitted::class);
    }

    #[Test]
    public function task_creation_and_status_change_fire_events(): void
    {
        $user = User::factory()->create();
        $training = Training::factory()->create([
            'user_id' => $user->id,
            'status' => TrainingStatus::IN_QUEUE,
            'area_id' => 1,
        ]);

        $task = Task::create([
            'type' => 'custom',
            'status' => TaskStatus::PENDING,
            'creator_user_id' => $user->id,
            'subject_user_id' => $user->id,
            'assignee_user_id' => $user->id,
            'subject_training_id' => $training->id,
        ]);
        $this->assertFired(TaskCreated::class);
        $this->assertNotFired(TaskStatusChanged::class);

        $task->update(['status' => TaskStatus::COMPLETED]);
        $this->assertFired(TaskStatusChanged::class);
    }
}
