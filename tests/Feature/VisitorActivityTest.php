<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Console\Commands\UpdateAtcHours;
use App\Helpers\VatsimRating;
use App\Models\ApiKey;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\Booking;
use App\Models\Endorsement;
use App\Models\Position;
use App\Models\Rating;
use App\Models\User;
use App\Notifications\InactivityNotification;
use App\Services\BookingService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class VisitorActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfSecond());
        config(['app.mode' => 'subdivision', 'app.owner_code' => 'SCA']);
        Setting::set('atcActivityQualificationPeriod', 12);
        Setting::set('atcActivityRequirement', 10);
        Setting::set('atcActivityGracePeriod', 12);
        Setting::set('atcActivityBasedOnTotalHours', true);
        Setting::set('atcActivityAllowReactivation', true);
        Notification::fake();
    }

    private function visitor(Area $area, array $attributes = []): User
    {
        $user = User::factory()->create(['subdivision' => 'OTHER', 'division' => 'OTHER', 'rating' => VatsimRating::C1]);
        $endorsement = Endorsement::factory()->create(array_merge([
            'user_id' => $user->id,
            'type' => 'VISITING',
            'valid_from' => now()->subMonth(),
            'valid_to' => now()->addMonth(),
            'revoked' => false,
            'expired' => false,
        ], $attributes));
        $endorsement->areas()->attach($area);

        return $user;
    }

    private function activity(User $user, Area $area, array $attributes = []): AtcActivity
    {
        return AtcActivity::create(array_merge([
            'user_id' => $user->id, 'area_id' => $area->id, 'hours' => 20,
            'hours_in_period' => 20, 'atc_active' => true,
        ], $attributes));
    }

    private function collectHours(User $user, array $sessions = []): void
    {
        $command = app(UpdateAtcHours::class);
        (new \ReflectionProperty($command, 'qualificationPeriod'))->setValue($command, 12);
        $command->setOutput(new OutputStyle(
            new ArrayInput([]),
            new BufferedOutput()
        ));
        $prefixes = Area::all()->mapWithKeys(fn (Area $area) => [
            $area->id => $area->positions->pluck('callsign')->map(fn ($callsign) => substr($callsign, 0, 4))->unique(),
        ]);
        (new \ReflectionMethod($command, 'updateHoursForMember'))->invoke($command, $user, collect($sessions), $prefixes);
    }

    public function test_new_visitors_are_collected_even_when_home_reactivation_is_disabled(): void
    {
        Setting::set('atcActivityAllowReactivation', false);
        $area = Area::factory()->create();
        $visitor = $this->visitor($area);
        $otherVisitor = $this->visitor($area);
        $this->app->instance(Client::class, new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode(['results' => []])),
        ]))]));

        $this->artisan('update:atc:hours', ['user' => [$visitor->id]])->assertSuccessful();

        $activity = $visitor->atcActivity()->sole();
        $this->assertTrue($activity->atc_active);
        $this->assertEquals(0, $activity->hours_in_period);
        $this->assertTrue($activity->start_of_grace_period->equalTo($visitor->endorsements()->sole()->valid_from));
        $this->assertTrue($visitor->isAtcActive());
        $this->assertTrue(User::getActiveAtcMembers()->contains($visitor));
        $this->assertSame(0, $otherVisitor->atcActivity()->count());
    }

    public function test_only_local_endorsed_area_hours_are_collected_and_new_records_include_period_hours(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        Position::factory()->create(['area_id' => $area->id, 'callsign' => 'EFHK_APP']);
        Position::factory()->create(['area_id' => $otherArea->id, 'callsign' => 'ESSA_APP']);
        $visitor = $this->visitor($area);
        $this->collectHours($visitor, [
            (object) ['callsign' => 'EFHK_APP', 'minutes_on_callsign' => 90, 'start' => now()->subDay()->toDateTimeString()],
            (object) ['callsign' => 'ESSA_APP', 'minutes_on_callsign' => 600, 'start' => now()->subDay()->toDateTimeString()],
            (object) ['callsign' => 'EGLL_APP', 'minutes_on_callsign' => 600, 'start' => now()->subDay()->toDateTimeString()],
        ]);

        $activity = $visitor->atcActivity()->sole();
        $this->assertEquals(1.5, $activity->hours_in_period);
        $this->assertEquals(1.5, $activity->hours);
        $this->assertSame($area->id, $activity->area_id);
        $this->assertTrue($activity->last_online->isSameDay(now()->subDay()));
    }

    public static function invalidEndorsements(): array
    {
        return [
            'revoked' => [['revoked' => true]],
            'expired flag' => [['expired' => true]],
            'expired date' => [['valid_to' => 'past']],
            'future start' => [['valid_from' => 'future']],
        ];
    }

    #[DataProvider('invalidEndorsements')]
    public function test_stale_activity_cannot_bypass_invalid_visiting_endorsements(array $attributes): void
    {
        if (($attributes['valid_to'] ?? null) === 'past') {
            $attributes['valid_to'] = now()->subSecond();
        }
        if (($attributes['valid_from'] ?? null) === 'future') {
            $attributes['valid_from'] = now()->addDay();
        }
        $area = Area::factory()->create();
        $visitor = $this->visitor($area, $attributes);
        $activity = $this->activity($visitor, $area);

        $this->assertFalse($visitor->isVisiting());
        $this->assertFalse($visitor->hasActiveEndorsement('VISITING'));
        $this->assertFalse($visitor->isAtcActive());
        $this->assertFalse($visitor->isAllowedToControlOnline($area));
        $this->assertFalse(User::getActiveAtcMembers()->contains($visitor));
        $this->assertFalse(User::getAssociatedActiveAtcMembers(false)->contains($visitor));
        $this->assertFalse(User::allActiveInArea($area)->contains($visitor));

        $activity->update(['atc_active' => false]);
        $this->artisan('update:atc:reactivate')->assertSuccessful();
        $this->assertFalse($activity->fresh()->atc_active);
    }

    public function test_multiple_endorsements_are_respected_without_leaking_activity_to_other_areas(): void
    {
        $firstArea = Area::factory()->create();
        $secondArea = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $visitor = $this->visitor($firstArea);
        $second = Endorsement::factory()->create([
            'user_id' => $visitor->id, 'type' => 'VISITING', 'valid_from' => now()->subMonth(), 'valid_to' => now()->addMonth(),
        ]);
        $second->areas()->attach($secondArea);
        $this->activity($visitor, $secondArea);
        $this->activity($visitor, $otherArea);

        $this->assertTrue($visitor->isVisiting($secondArea));
        $this->assertTrue($visitor->isAtcActive($secondArea));
        $this->assertFalse($visitor->isAtcActive($otherArea));
        $this->assertFalse($visitor->isAllowedToControlOnline($otherArea));
        $this->assertCount(1, $visitor->localAtcActivities());
    }

    public function test_visitor_hours_outside_endorsed_areas_cannot_reactivate_them(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $visitor = $this->visitor($area, ['valid_from' => now()->subYears(2)]);
        $activity = $this->activity($visitor, $area, ['hours' => 1, 'atc_active' => false]);
        $this->activity($visitor, $otherArea, ['hours' => 100, 'atc_active' => false]);

        $this->artisan('update:atc:reactivate')->assertSuccessful();
        $this->assertFalse($activity->fresh()->atc_active);
        $activity->update(['hours' => 10]);
        $this->artisan('update:atc:reactivate')->assertSuccessful();
        $this->assertTrue($activity->fresh()->atc_active);
        $this->assertFalse($visitor->atcActivity()->where('area_id', $otherArea->id)->sole()->atc_active);
    }

    public function test_collecting_hours_does_not_reset_an_existing_visitors_grace_period(): void
    {
        $area = Area::factory()->create();
        $visitor = $this->visitor($area);
        $graceStart = now()->subYears(2);
        $activity = $this->activity($visitor, $area, ['start_of_grace_period' => $graceStart, 'atc_active' => false]);

        $this->collectHours($visitor);

        $this->assertTrue($activity->fresh()->start_of_grace_period->equalTo($graceStart));
        $this->assertFalse($activity->fresh()->atc_active);
    }

    public function test_member_maintenance_preserves_visitors_only_in_currently_endorsed_areas(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $visitor = $this->visitor($area);
        $allowed = $this->activity($visitor, $area);
        $disallowed = $this->activity($visitor, $otherArea);
        Setting::set('trainingSubDivisions', 'SCA');

        $this->artisan('update:member:details')->assertSuccessful();

        $this->assertTrue($allowed->fresh()->atc_active);
        $this->assertFalse($disallowed->fresh()->atc_active);
        $visitor->endorsements()->update(['revoked' => true]);
        $this->artisan('update:member:details')->assertSuccessful();
        $this->assertFalse($allowed->fresh()->atc_active);
    }

    public function test_api_exposes_active_visitors_as_controllers_but_only_in_endorsed_areas(): void
    {
        $area = Area::factory()->create(['name' => 'Alpha']);
        $otherArea = Area::factory()->create(['name' => 'Beta']);
        $visitor = $this->visitor($area);
        $this->activity($visitor, $area);
        $this->activity($visitor, $otherArea);
        ApiKey::create(['id' => 'visitor-api-key', 'name' => 'test', 'read_only' => true, 'created_at' => now()]);

        $this->withToken('visitor-api-key')
            ->getJson('/api/v1/users?include[]=allUsers&include[]=activeAreas&onlyAtcActive=true')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visitor->id)
            ->assertJsonPath('data.0.atc_active', true)
            ->assertJsonPath('data.0.atc_active_areas.alpha', true)
            ->assertJsonPath('data.0.atc_active_areas.beta', false);

        $visitor->endorsements()->update(['revoked' => true]);
        $this->withToken('visitor-api-key')
            ->getJson('/api/v1/users?include[]=allUsers&include[]=endorsements&onlyAtcActive=true')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_feedback_lists_valid_visitors_even_without_activity(): void
    {
        $area = Area::factory()->create();
        $visitor = $this->visitor($area);
        $expired = $this->visitor($area, ['expired' => true]);
        Setting::set('feedbackEnabled', true);

        $this->actingAs(User::factory()->create())->get(route('feedback'))
            ->assertOk()->assertViewHas('controllers', fn ($controllers) => $controllers->contains($visitor) && ! $controllers->contains($expired));
    }

    public function test_visitors_cannot_use_local_activity_to_book_outside_their_endorsed_areas(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $visitor = $this->visitor($area);
        $allowed = Position::factory()->create(['area_id' => $area->id, 'rating' => VatsimRating::S1]);
        $disallowed = Position::factory()->create(['area_id' => $otherArea->id, 'rating' => VatsimRating::S1]);
        $this->activity($visitor, $area);

        $positions = app(BookingService::class)->getBookablePositions($visitor);
        $this->assertTrue($positions->contains($allowed));
        $this->assertFalse($positions->contains($disallowed));
        $booking = new Booking(['position_id' => $disallowed->id]);
        $this->assertTrue(Gate::forUser($visitor)->denies('position', $booking));
    }

    public function test_visitors_become_inactive_using_the_normal_activity_requirement(): void
    {
        $area = Area::factory()->create();
        $visitor = $this->visitor($area, ['valid_from' => now()->subYears(2)]);
        $activity = $this->activity($visitor, $area, ['hours' => 1]);

        $this->artisan('update:atc:activity')->assertSuccessful();

        $this->assertFalse($activity->fresh()->atc_active);
        $this->assertFalse($visitor->isAtcActive());
        $this->assertTrue($visitor->isVisiting());
        Notification::assertSentTo($visitor, InactivityNotification::class);
    }

    public function test_per_area_reactivation_respects_endorsed_areas(): void
    {
        Setting::set('atcActivityBasedOnTotalHours', false);
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $visitor = $this->visitor($area);
        $allowed = $this->activity($visitor, $area, ['atc_active' => false]);
        $disallowed = $this->activity($visitor, $otherArea, ['atc_active' => false]);

        $this->artisan('update:atc:reactivate')->assertSuccessful();

        $this->assertTrue($allowed->fresh()->atc_active);
        $this->assertFalse($disallowed->fresh()->atc_active);
    }

    public function test_visiting_list_displays_hours_last_session_and_expiry_for_renewal_review(): void
    {
        $area = Area::factory()->create();
        $visitor = $this->visitor($area);
        $visitor->endorsements()->sole()->ratings()->attach(Rating::factory()->create(['vatsim_rating' => VatsimRating::C1]));
        $this->activity($visitor, $area, ['hours_in_period' => 12.5, 'last_online' => now()->subDay()]);

        $this->actingAs(User::factory()->create())->get(route('endorsements.visiting'))
            ->assertOk()->assertSee('Local hours')->assertSee('Last local session')->assertSee('Visiting expiry')
            ->assertSee('12.5')->assertSee(now()->subDay()->format('Y-m-d'))->assertSee(now()->addMonth()->format('Y-m-d'));

        $visitor->endorsements()->update(['expired' => true]);
        $this->get(route('endorsements.visiting'))->assertOk()->assertSee('12.5')->assertSee('Inactive');
    }

    public static function membershipModes(): array
    {
        return ['subdivision' => ['subdivision'], 'division' => ['division']];
    }

    #[DataProvider('membershipModes')]
    public function test_home_and_visiting_controllers_share_the_active_roster_in_both_membership_modes(string $mode): void
    {
        config(['app.mode' => $mode]);
        $area = Area::factory()->create();
        $home = User::factory()->home()->create(['rating' => VatsimRating::C1]);
        $visitor = $this->visitor($area);
        $outsider = User::factory()->create(['division' => 'OTHER', 'subdivision' => 'OTHER']);
        foreach ([$home, $visitor, $outsider] as $controller) {
            $this->activity($controller, $area);
        }

        $this->assertTrue($home->isAtcActive());
        $this->assertTrue($visitor->isAtcActive());
        $this->assertFalse($outsider->isAtcActive());
        $this->assertEqualsCanonicalizing([$home->id, $visitor->id], User::getActiveAtcMembers()->modelKeys());
    }

    public function test_first_collection_can_activate_a_visitor_using_hours_across_multiple_endorsed_areas(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        Position::factory()->create(['area_id' => $area->id, 'callsign' => 'EFHK_APP']);
        Position::factory()->create(['area_id' => $otherArea->id, 'callsign' => 'ESSA_APP']);
        $visitor = $this->visitor($area, ['valid_from' => now()->subYears(2)]);
        $visitor->endorsements()->sole()->areas()->attach($otherArea);

        $this->collectHours($visitor, [
            (object) ['callsign' => 'EFHK_APP', 'minutes_on_callsign' => 360, 'start' => now()->subDay()->toDateTimeString()],
            (object) ['callsign' => 'ESSA_APP', 'minutes_on_callsign' => 360, 'start' => now()->subDay()->toDateTimeString()],
        ]);

        $this->assertTrue($visitor->isAtcActive());
        $this->assertSame(2, $visitor->atcActivity()->where('atc_active', true)->count());
    }
}
