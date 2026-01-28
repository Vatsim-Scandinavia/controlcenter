<?php

namespace Tests\Feature;

use anlutro\LaravelSettings\Facade as Setting;
use App\Console\Commands\UpdateAtcHours;
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
use Tests\TestCase;

class UpdateAtcHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Settings
        Setting::shouldReceive('get')->with('atcActivityAllowReactivation')->andReturn(false);
        // We will mock qualificationPeriod in tests
    }

    public function test_it_calculates_hours_based_on_custom_qualification_period()
    {
        // Set qualification period to 6 months
        Setting::shouldReceive('get')->with('atcActivityQualificationPeriod', 12)->andReturn(6);
        Setting::shouldReceive('get')->with('atcActivityRequirement', 10)->andReturn(10); // used in User::allActiveInArea? No, command uses hardcoded or setting?
        // Command uses Setting::get('atcActivityQualificationPeriod', 12);

        // Setup Data
        $area = Area::factory()->create();
        $position = Position::forceCreate([
            'callsign' => 'EFHK_APP',
            'area_id' => $area->id,
            'frequency' => '119.100',
            'name' => 'Helsinki Radar',
            'fir' => 'EFIN',
            'rating' => 4,
        ]); // Assuming Position factory/create

        $user = User::factory()->create(['id' => 123456, 'rating' => 5]); // S3

        // Setup User as associated (User::getAssociatedActiveAtcMembers) checks config('app.mode')
        // Default app.mode in .env.testing?
        Config::set('app.mode', 'division');
        Config::set('app.owner_code', 'VATSCA');
        $user->division = 'VATSCA';
        $user->save();

        // Create initial AtcActivity so user is picked up (active controllers)
        AtcActivity::create(['user_id' => $user->id, 'area_id' => $area->id, 'hours' => 10, 'atc_active' => true]);

        // Mock API Response
        // 1 session 3 months ago (inside 6 months)
        // 1 session 9 months ago (outside 6 months)
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

        // Run Command
        $this->artisan('update:atc:hours')
            ->assertExitCode(0);

        // Verify
        $activity = AtcActivity::where('user_id', $user->id)->where('area_id', $area->id)->first();

        // Only 1 hour should be counted in period (60 mins)
        // Note: The command sums minutes / 60.
        $this->assertEquals(1.0, $activity->hours_in_period, 'Hours in period should be 1.0 (only recent session)');

        // Verify total hours logic? The command logic for total hours ($hoursActiveInArea) does NOT filter by period in the code I read?
        // Let's check UpdateAtcHours.php again.
        // $hoursActiveInArea = $sessions->filter(...)->map(...)->sum() / 60;
        // It does NOT use $periodStart.
        // So total hours should include both sessions (2 hours).

        $this->assertEquals(2.0, $activity->hours, 'Total hours should be 2.0 (all sessions)');
    }
}
