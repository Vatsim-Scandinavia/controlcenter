<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Facades\DivisionApi;
use App\Helpers\TaskStatus;
use App\Helpers\VatsimRating;
use App\Models\AtcActivity;
use App\Models\Task;
use App\Models\Training;
use App\Models\User;
use App\Tasks\Types\RatingUpgrade;
use Carbon\Carbon;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response as ClientResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SyncRosterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('divisionApiEnabled', true);
    }

    /**
     * Fake the Core roster so it contains exactly these VATSIM CIDs.
     */
    private function fakeRosterContaining(array $userIds): void
    {
        $members = array_map(fn (int $id) => ['user_cid' => $id], $userIds);

        DivisionApi::shouldReceive('getRoster')->once()->andReturn(
            new ClientResponse(new GuzzleResponse(200, [], json_encode(['data' => ['roster_members' => $members]])))
        );
    }

    /**
     * A student Core has already been asked to upgrade, whose local VATSIM rating has not
     * caught up yet: active for ATC locally, but still OBS, so the rating >= S1 gate in
     * getActiveAtcMembers() leaves them out of the active set.
     */
    private function upgradedButLaggingUser(): User
    {
        $user = User::factory()->create(['rating' => VatsimRating::OBS]);

        AtcActivity::create([
            'user_id' => $user->id,
            'area_id' => 1,
            'hours' => 0,
            'atc_active' => true,
        ]);

        return $user;
    }

    /**
     * The record left behind by a rating upgrade request.
     */
    private function ratingUpgradeTask(User $user, Carbon $closedAt, TaskStatus $status = TaskStatus::COMPLETED): Task
    {
        return Task::create([
            'type' => RatingUpgrade::class,
            'status' => $status,
            'subject_user_id' => $user->id,
            'subject_training_id' => Training::factory()->create(['user_id' => $user->id])->id,
            'assignee_user_id' => User::factory()->create()->id,
            'closed_at' => $closedAt,
        ]);
    }

    private function successfulResponse(): ClientResponse
    {
        return new ClientResponse(new GuzzleResponse(200, [], json_encode(['status' => 'ok'])));
    }

    #[Test]
    public function a_recent_rating_upgrade_keeps_a_lagging_student_on_the_roster(): void
    {
        $user = $this->upgradedButLaggingUser();
        $this->ratingUpgradeTask($user, now()->subDay());
        $this->fakeRosterContaining([$user->id]);

        // Removing them here would undo the upgrade we just asked Core for.
        DivisionApi::shouldReceive('removeRosterUser')->never();
        DivisionApi::shouldReceive('assignRosterUser')->never();

        $this->artisan('sync:roster');
    }

    #[Test]
    public function a_student_without_a_recent_upgrade_is_still_removed(): void
    {
        // Indistinguishable from a suspended or demoted member: atc_active locally, rating
        // below S1, and no pending upgrade to protect them.
        $user = $this->upgradedButLaggingUser();
        $this->fakeRosterContaining([$user->id]);

        DivisionApi::shouldReceive('removeRosterUser')->once()->with($user->id)->andReturn($this->successfulResponse());

        $this->artisan('sync:roster');
    }

    #[Test]
    public function an_upgrade_that_is_stale_or_not_completed_does_not_protect(): void
    {
        $stale = $this->upgradedButLaggingUser();
        $this->ratingUpgradeTask($stale, now()->subDays(31));

        // closed_at is recent on purpose, so this pins the status filter rather than the window.
        $pending = $this->upgradedButLaggingUser();
        $this->ratingUpgradeTask($pending, now()->subDay(), TaskStatus::PENDING);

        $this->fakeRosterContaining([$stale->id, $pending->id]);

        DivisionApi::shouldReceive('removeRosterUser')->twice()->andReturn($this->successfulResponse());

        $this->artisan('sync:roster');
    }
}
