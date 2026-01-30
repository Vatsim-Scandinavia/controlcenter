<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Helpers\VatsimRating;
use App\Models\Area;
use App\Models\AtcActivity;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateAtcHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::shouldReceive('get')->with('atcActivityAllowReactivation')->andReturn(false);
    }

    /**
     * Tests that hours are calculated correctly based on a custom qualification period.
     *
     * Sets qualification period to 6 months and verifies:
     * - Only sessions within the qualification period are counted in hours_in_period
     * - All sessions (regardless of period) are counted in total hours
     *
     * Creates two sessions: one 3 months ago (within 6-month period) and one 9 months ago (outside period).
     * Expects hours_in_period to be 1.0 (only the recent session) and total hours to be 2.0 (both sessions).
     */
    #[Test]
    public function it_calculates_hours_based_on_custom_qualification_period(): void
    {
        Setting::shouldReceive('get')->with('atcActivityQualificationPeriod', 12)->andReturn(6);
        Setting::shouldReceive('get')->with('atcActivityRequirement', 10)->andReturn(10);

        $area = Area::factory()->create();
        Position::forceCreate([
            'callsign' => 'EFHK_APP',
            'area_id' => $area->id,
            'frequency' => '119.100',
            'name' => 'Helsinki Radar',
            'fir' => 'EFIN',
            'rating' => 4,
        ]);

        $user = User::factory()->create(['rating' => VatsimRating::C1->value]);

        Config::set('app.mode', 'division');
        Config::set('app.owner_code', 'VATSCA');
        $user->division = 'VATSCA';
        $user->save();

        AtcActivity::create([
            'user_id' => $user->id,
            'area_id' => $area->id,
            'hours' => 10,
            'atc_active' => true,
        ]);

        $now = Carbon::now();
        $results = [
            (object) [
                'callsign' => 'EFHK_APP',
                'minutes_on_callsign' => '60',
                'start' => $now->copy()->subMonths(3)->toDateTimeString(),
            ],
            (object) [
                'callsign' => 'EFHK_APP',
                'minutes_on_callsign' => '60',
                'start' => $now->copy()->subMonths(9)->toDateTimeString(),
            ],
        ];

        $mockBody = json_encode(['results' => $results]);
        $mock = new MockHandler([
            new Response(200, [], $mockBody),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $this->app->instance(Client::class, $client);

        $this->artisan('update:atc:hours')
            ->assertExitCode(0);

        $activity = AtcActivity::where('user_id', $user->id)
            ->where('area_id', $area->id)
            ->first();

        $this->assertEquals(1.0, $activity->hours_in_period, 'Hours in period should be 1.0 (only recent session)');
        $this->assertEquals(2.0, $activity->hours, 'Total hours should be 2.0 (all sessions)');
    }
}
