<?php

namespace Tests\Feature;

use App\Services\PilotFlightHistoryService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PilotFlightHistoryServiceTest extends TestCase
{
    private const HISTORY_URL = 'api.vatsim.net/v2/members/1000000/history*';

    private const FLIGHT_PLANS_URL = 'api.vatsim.net/v2/members/1000000/flightplans';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        $this->travelTo('2026-10-31 12:00:00');
    }

    /**
     * @return array<string, mixed>
     */
    private function connection(int $id, string $callsign, string $start, int $type = 1): array
    {
        return ['id' => $id, 'type' => $type, 'callsign' => $callsign, 'start' => $start, 'end' => null];
    }

    #[Test]
    public function it_merges_connections_with_the_latest_flight_plan_of_each(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['count' => 1, 'items' => [
                $this->connection(11, 'CSA123', '2026-10-27T17:47:58Z'),
            ]]),
            self::FLIGHT_PLANS_URL => Http::response([
                ['connection_id' => 11, 'dep' => 'LKPR', 'arr' => 'EGLL'],
                ['connection_id' => 11, 'dep' => 'LKPR', 'arr' => 'EDDF'],
            ]),
        ]);

        $flights = app(PilotFlightHistoryService::class)->recentFlights(1000000);

        $this->assertSame([[
            'callsign' => 'CSA123',
            'date' => '2026-10-27',
            'departure' => 'LKPR',
            'arrival' => 'EGLL',
        ]], $flights);
    }

    #[Test]
    public function it_keeps_flights_without_a_flight_plan(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['items' => [$this->connection(11, 'CSA123', '2026-10-27T17:47:58Z')]]),
            self::FLIGHT_PLANS_URL => Http::response([]),
        ]);

        $flights = app(PilotFlightHistoryService::class)->recentFlights(1000000);

        $this->assertNull($flights[0]['departure']);
        $this->assertNull($flights[0]['arrival']);
    }

    #[Test]
    public function it_ignores_controller_and_malformed_connections(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['items' => [
                $this->connection(11, 'LKPR_TWR', '2026-10-27T17:47:58Z', 2),
                $this->connection(12, '', '2026-10-27T17:47:58Z'),
                $this->connection(13, 'CSA123', 'not a date'),
                'garbage',
            ]]),
            self::FLIGHT_PLANS_URL => Http::response([]),
        ]);

        $this->assertSame([], app(PilotFlightHistoryService::class)->recentFlights(1000000));
    }

    #[Test]
    public function it_ignores_flights_older_than_thirty_days(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['items' => [
                $this->connection(12, 'CSA123', '2026-10-02T12:00:00Z'),
                $this->connection(11, 'CSA456', '2026-09-30T12:00:00Z'),
            ]]),
            self::FLIGHT_PLANS_URL => Http::response([]),
        ]);

        $flights = app(PilotFlightHistoryService::class)->recentFlights(1000000);

        $this->assertSame(['CSA123'], array_column($flights, 'callsign'));
    }

    #[Test]
    public function it_ignores_flights_starting_after_today(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['items' => [
                $this->connection(12, 'CSA456', '2026-11-01T00:00:00Z'),
                $this->connection(11, 'CSA123', '2026-10-31T23:59:59Z'),
            ]]),
            self::FLIGHT_PLANS_URL => Http::response([]),
        ]);

        $flights = app(PilotFlightHistoryService::class)->recentFlights(1000000);

        $this->assertSame(['CSA123'], array_column($flights, 'callsign'));
    }

    #[Test]
    public function it_lists_every_connection_as_reported_without_merging(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['items' => [
                $this->connection(12, 'CSA123', '2026-10-27T19:00:00Z'),
                $this->connection(11, 'CSA123', '2026-10-27T17:00:00Z'),
            ]]),
            self::FLIGHT_PLANS_URL => Http::response([
                ['connection_id' => 11, 'dep' => 'LKPR', 'arr' => 'EGLL'],
            ]),
        ]);

        $flights = app(PilotFlightHistoryService::class)->recentFlights(1000000);

        $this->assertCount(2, $flights);
        $this->assertNull($flights[0]['departure']);
        $this->assertSame('LKPR', $flights[1]['departure']);
    }

    #[Test]
    public function it_still_returns_flights_when_flight_plans_fail(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['items' => [$this->connection(11, 'CSA123', '2026-10-27T17:47:58Z')]]),
            self::FLIGHT_PLANS_URL => Http::response([], 500),
        ]);

        $flights = app(PilotFlightHistoryService::class)->recentFlights(1000000);

        $this->assertSame('CSA123', $flights[0]['callsign']);
        $this->assertNull($flights[0]['departure']);
    }

    #[Test]
    public function it_returns_nothing_when_the_api_errors_and_does_not_ask_again_right_away(): void
    {
        Http::fake([self::HISTORY_URL => Http::response([], 503)]);

        $service = app(PilotFlightHistoryService::class);

        $this->assertSame([], $service->recentFlights(1000000));
        $this->assertSame([], $service->recentFlights(1000000));

        Http::assertSentCount(1);
    }

    #[Test]
    public function it_returns_nothing_when_the_api_is_unreachable(): void
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->assertSame([], app(PilotFlightHistoryService::class)->recentFlights(1000000));
    }

    #[Test]
    public function it_survives_wrongly_typed_fields(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['items' => [
                [...$this->connection(11, 'CSA123', '2026-10-27T17:47:58Z'), 'id' => [11]],
                [...$this->connection(12, 'CSA123', '2026-10-27T17:47:58Z'), 'callsign' => ['CSA123']],
                [...$this->connection(13, 'CSA123', '2026-10-27T17:47:58Z'), 'type' => '1'],
                [...$this->connection(14, 'CSA123', '2026-10-27T17:47:58Z'), 'start' => 12345],
                $this->connection(15, 'CSA456', '2026-10-27T17:47:58Z'),
            ]]),
            self::FLIGHT_PLANS_URL => Http::response([
                ['connection_id' => [15], 'dep' => 'LKPR'],
                ['connection_id' => 15, 'dep' => ['LKPR'], 'arr' => 12],
                'garbage',
            ]),
        ]);

        $this->assertSame([[
            'callsign' => 'CSA456',
            'date' => '2026-10-27',
            'departure' => null,
            'arrival' => null,
        ]], app(PilotFlightHistoryService::class)->recentFlights(1000000));
    }

    #[Test]
    public function it_drops_airports_that_are_not_icao_codes(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['items' => [$this->connection(11, 'CSA123', '2026-10-27T17:47:58Z')]]),
            self::FLIGHT_PLANS_URL => Http::response([['connection_id' => 11, 'dep' => 'LAX', 'arr' => 'EGLL']]),
        ]);

        $flight = app(PilotFlightHistoryService::class)->recentFlights(1000000)[0];

        $this->assertNull($flight['departure']);
        $this->assertSame('EGLL', $flight['arrival']);
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function unusableHistoryResponses(): array
    {
        return [
            'html' => ['<html>busy</html>', 200],
            'empty body' => ['', 200],
            'no items' => ['{"count": 0}', 200],
            'items not a list' => ['{"items": "none"}', 200],
            'scalar' => ['"none"', 200],
            'no content' => ['', 204],
            'redirect' => ['', 302],
            'rate limited' => ['', 429],
            'not found' => ['', 404],
        ];
    }

    #[Test]
    #[DataProvider('unusableHistoryResponses')]
    public function it_treats_an_unusable_response_as_a_failure_and_retries_soon(string $body, int $status): void
    {
        Http::fake([self::HISTORY_URL => Http::response($body, $status)]);

        $service = app(PilotFlightHistoryService::class);

        $this->assertSame([], $service->recentFlights(1000000));
        $service->recentFlights(1000000);
        Http::assertSentCount(1);

        $this->travel(2)->minutes();

        $service->recentFlights(1000000);
        Http::assertSentCount(2);
    }

    #[Test]
    public function it_does_not_follow_redirects(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response('', 302, ['Location' => 'https://example.com/elsewhere']),
            'example.com/*' => Http::response(['items' => [$this->connection(11, 'CSA123', '2026-10-27T17:47:58Z')]]),
        ]);

        $this->assertSame([], app(PilotFlightHistoryService::class)->recentFlights(1000000));
    }

    #[Test]
    public function it_retries_soon_when_only_flight_plans_failed(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['items' => [$this->connection(11, 'CSA123', '2026-10-27T17:47:58Z')]]),
            self::FLIGHT_PLANS_URL => Http::sequence()
                ->push('not json', 200)
                ->push([['connection_id' => 11, 'dep' => 'LKPR', 'arr' => 'EGLL']]),
        ]);

        $service = app(PilotFlightHistoryService::class);

        $this->assertNull($service->recentFlights(1000000)[0]['departure']);

        $this->travel(2)->minutes();

        $this->assertSame('LKPR', $service->recentFlights(1000000)[0]['departure']);
    }

    #[Test]
    public function it_keeps_flights_when_flight_plans_are_unreachable(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/flightplans')) {
                throw new ConnectionException('timed out');
            }

            return Http::response(['items' => [$this->connection(11, 'CSA123', '2026-10-27T17:47:58Z')]]);
        });

        $this->assertSame('CSA123', app(PilotFlightHistoryService::class)->recentFlights(1000000)[0]['callsign']);
    }

    #[Test]
    public function it_returns_nothing_for_a_member_without_flights(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['count' => 0, 'items' => []]),
            self::FLIGHT_PLANS_URL => Http::response([]),
        ]);

        $this->assertSame([], app(PilotFlightHistoryService::class)->recentFlights(1000000));
    }

    #[Test]
    public function a_late_failure_does_not_replace_flights_cached_by_a_concurrent_request(): void
    {
        $service = app(PilotFlightHistoryService::class);
        $concurrentRequestFinished = false;

        Http::fake(function (Request $request) use ($service, &$concurrentRequestFinished) {
            if (str_contains($request->url(), '/flightplans')) {
                return Http::response([]);
            }

            if (! $concurrentRequestFinished) {
                $concurrentRequestFinished = true;
                $service->recentFlights(1000000);

                return Http::response([], 503);
            }

            return Http::response(['items' => [$this->connection(11, 'CSA123', '2026-10-27T17:47:58Z')]]);
        });

        $this->assertSame([], $service->recentFlights(1000000));
        $this->assertSame('CSA123', $service->recentFlights(1000000)[0]['callsign']);
    }

    #[Test]
    public function it_keeps_the_cache_of_each_member_apart(): void
    {
        Http::fake([
            'api.vatsim.net/v2/members/1000000/history*' => Http::response(['items' => [$this->connection(11, 'CSA123', '2026-10-27T17:47:58Z')]]),
            'api.vatsim.net/v2/members/2000000/history*' => Http::response(['items' => [$this->connection(12, 'CSA456', '2026-10-27T17:47:58Z')]]),
            'api.vatsim.net/v2/members/*/flightplans' => Http::response([]),
        ]);

        $service = app(PilotFlightHistoryService::class);

        $this->assertSame('CSA123', $service->recentFlights(1000000)[0]['callsign']);
        $this->assertSame('CSA456', $service->recentFlights(2000000)[0]['callsign']);
    }

    #[Test]
    public function it_caches_flights_per_member(): void
    {
        Http::fake([
            self::HISTORY_URL => Http::response(['items' => [$this->connection(11, 'CSA123', '2026-10-27T17:47:58Z')]]),
            self::FLIGHT_PLANS_URL => Http::response([]),
        ]);

        $service = app(PilotFlightHistoryService::class);
        $service->recentFlights(1000000);
        $service->recentFlights(1000000);

        Http::assertSentCount(2);
    }
}
