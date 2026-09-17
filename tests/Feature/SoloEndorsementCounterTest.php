<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\TrainingStatus;
use App\Models\Endorsement;
use App\Models\Training;
use App\Models\TrainingActivity;
use App\Models\User;
use App\Services\SoloEndorsementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SoloEndorsementCounterTest extends TestCase
{
    use RefreshDatabase;

    private Training $training;

    private SoloEndorsementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->training = Training::factory()->create([
            'user_id' => User::factory()->create()->id,
            'status' => TrainingStatus::ACTIVE_TRAINING,
        ]);

        $this->service = app(SoloEndorsementService::class);
    }

    /**
     * Issue a solo endorsement and record it against the training the way
     * EndorsementController does.
     */
    private function grantSolo(?Carbon $from, ?Carbon $to, bool $linked = true, array $attributes = []): Endorsement
    {
        $endorsement = Endorsement::factory()->create([
            'user_id' => $this->training->user_id,
            'type' => 'SOLO',
            'valid_from' => $from,
            'valid_to' => $to,
            ...$attributes,
        ]);

        if ($linked) {
            $activity = new TrainingActivity();
            $activity->training_id = $this->training->id;
            $activity->type = 'ENDORSEMENT';
            $activity->new_data = $endorsement->id;
            $activity->save();
        }

        return $endorsement;
    }

    private function moderator(): User
    {
        $moderator = User::factory()->create();
        $moderator->roleAssignments()->create(['role' => 'moderator', 'area_id' => $this->training->area->id]);

        return $moderator;
    }

    #[Test]
    public function a_training_without_solo_endorsements_has_no_summary()
    {
        $this->assertNull($this->service->summaryFor($this->training));
    }

    #[Test]
    public function it_totals_the_days_granted_across_linked_endorsements()
    {
        $this->grantSolo(Carbon::parse('2026-01-01 12:00'), Carbon::parse('2026-01-31 12:00'));
        $this->grantSolo(Carbon::parse('2026-02-01 12:00'), Carbon::parse('2026-02-21 12:00'));

        $summary = $this->service->summaryFor($this->training->refresh());

        $this->assertSame(50, $summary['used']);
        $this->assertSame(10, $summary['left']);
        $this->assertSame(60, $summary['total']);
        $this->assertSame(83, $summary['percentage_used']);
        $this->assertCount(2, $summary['endorsements']);
    }

    #[Test]
    public function it_ignores_solo_endorsements_not_recorded_against_the_training()
    {
        $this->grantSolo(Carbon::parse('2026-01-01 12:00'), Carbon::parse('2026-01-11 12:00'));
        // Issued on a different training, so it must not count here.
        $this->grantSolo(Carbon::parse('2026-03-01 12:00'), Carbon::parse('2026-03-31 12:00'), linked: false);

        $summary = $this->service->summaryFor($this->training->refresh());

        $this->assertSame(10, $summary['used']);
        $this->assertCount(1, $summary['endorsements']);
    }

    #[Test]
    public function an_endorsement_without_an_end_date_grants_no_countable_days()
    {
        $this->grantSolo(Carbon::parse('2026-01-01 12:00'), null);

        $summary = $this->service->summaryFor($this->training->refresh());

        $this->assertSame(0, $summary['used']);
        $this->assertSame(60, $summary['left']);
    }

    #[Test]
    public function the_allowance_comes_from_the_global_setting()
    {
        Setting::set('trainingSoloMaxDays', 30);
        Setting::save();

        $this->grantSolo(Carbon::parse('2026-01-01 12:00'), Carbon::parse('2026-01-11 12:00'));

        $summary = $this->service->summaryFor($this->training->refresh());

        $this->assertSame(30, $summary['total']);
        $this->assertSame(20, $summary['left']);
    }

    #[Test]
    public function overspending_the_allowance_clamps_at_zero_left_and_full_percentage()
    {
        Setting::set('trainingSoloMaxDays', 10);
        Setting::save();

        $this->grantSolo(Carbon::parse('2026-01-01 12:00'), Carbon::parse('2026-02-10 12:00'));

        $summary = $this->service->summaryFor($this->training->refresh());

        $this->assertSame(40, $summary['used']);
        $this->assertSame(0, $summary['left']);
        $this->assertSame(100, $summary['percentage_used']);
        $this->assertTrue($summary['collapsed']);
    }

    #[Test]
    public function revoked_endorsements_still_count_towards_the_days_issued()
    {
        $this->grantSolo(Carbon::parse('2026-01-01 12:00'), Carbon::parse('2026-01-21 12:00'), attributes: ['revoked' => true]);

        $summary = $this->service->summaryFor($this->training->refresh());

        $this->assertSame(20, $summary['used']);
    }

    #[Test]
    public function the_counter_is_shown_on_the_training_page()
    {
        $this->grantSolo(Carbon::parse('2026-01-01 12:00'), Carbon::parse('2026-01-21 12:00'));

        $this->actingAs($this->moderator())
            ->get(route('training.show', $this->training))
            ->assertOk()
            ->assertSee('Solo Endorsements')
            ->assertSee('Solo days remaining:');
    }

    #[Test]
    public function the_counter_is_hidden_when_no_solo_was_issued()
    {
        $this->actingAs($this->moderator())
            ->get(route('training.show', $this->training))
            ->assertOk()
            ->assertDontSee('Solo days remaining:');
    }
}
