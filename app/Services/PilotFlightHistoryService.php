<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

/**
 * Reads a member's recent pilot flights from the public VATSIM Core API, so the
 * feedback form can offer them as a shortcut. The form must keep working when
 * the API does not, so failures yield fewer details instead of an exception.
 */
class PilotFlightHistoryService
{
    private const BASE_URL = 'https://api.vatsim.net/v2/members/';

    private const PILOT_CONNECTION_TYPE = 1;

    private const FLIGHT_LIMIT = 15;

    private const RECENT_DAYS = 30;

    private const CONNECT_TIMEOUT_SECONDS = 2;

    private const REQUEST_TIMEOUT_SECONDS = 3;

    private const CACHE_MINUTES = 10;

    /**
     * Spares every form load from waiting on an API outage, while the flights
     * return soon after it ends.
     */
    private const FAILURE_CACHE_MINUTES = 1;

    /**
     * Pilot connections of a member from the last RECENT_DAYS days, as reported
     * by VATSIM, newest first. Each connection is listed as is. Airports are
     * null when the connection has no flight plan or filed no ICAO code.
     *
     * @return array<int, array{callsign: string, date: string, departure: ?string, arrival: ?string}>
     */
    public function recentFlights(int $cid): array
    {
        $cacheKey = sprintf('vatsim:flights:%d', $cid);

        $cachedFlights = Cache::get($cacheKey);
        if (is_array($cachedFlights)) {
            return $cachedFlights;
        }

        try {
            $connections = $this->fetchConnections($cid);
        } catch (ConnectionException|RequestException|UnexpectedValueException $e) {
            $this->logFailure('connections', $cid, $e);
            Cache::add($cacheKey, [], now()->addMinutes(self::FAILURE_CACHE_MINUTES));

            return [];
        }

        $cacheMinutes = self::CACHE_MINUTES;

        try {
            $flightPlans = $this->fetchFlightPlans($cid);
        } catch (ConnectionException|RequestException|UnexpectedValueException $e) {
            $this->logFailure('flight plans', $cid, $e);
            $flightPlans = collect();
            $cacheMinutes = self::FAILURE_CACHE_MINUTES;
        }

        $flights = $this->buildFlights($connections, $flightPlans);
        Cache::put($cacheKey, $flights, now()->addMinutes($cacheMinutes));

        return $flights;
    }

    /**
     * @param  Collection<int, array<mixed, mixed>>  $connections
     * @param  Collection<array-key, mixed>  $flightPlans
     * @return array<int, array{callsign: string, date: string, departure: ?string, arrival: ?string}>
     */
    private function buildFlights(Collection $connections, Collection $flightPlans): array
    {
        return $connections
            ->map(function (array $connection) use ($flightPlans): array {
                $flightPlan = $flightPlans->get($connection['id']);

                return [
                    'callsign' => $connection['callsign'],
                    'date' => Carbon::parse($connection['start'])->utc()->toDateString(),
                    'departure' => $this->icaoCode($flightPlan['dep'] ?? null),
                    'arrival' => $this->icaoCode($flightPlan['arr'] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return Collection<int|string, array<mixed, mixed>>
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws UnexpectedValueException
     */
    private function fetchConnections(int $cid): Collection
    {
        $connections = $this->fetchJson($cid . '/history', ['limit' => self::FLIGHT_LIMIT])['items'] ?? null;

        if (! is_array($connections)) {
            throw new UnexpectedValueException('The response has no list of connections.');
        }

        return collect($connections)->filter(fn (mixed $connection): bool => $this->isRecentPilotConnection($connection));
    }

    /**
     * The latest flight plan of each connection. The API lists newest first, so
     * reversing lets the newest plan win keyBy().
     *
     * @return Collection<array-key, mixed>
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws UnexpectedValueException
     */
    private function fetchFlightPlans(int $cid): Collection
    {
        return collect($this->fetchJson($cid . '/flightplans'))
            ->filter(fn (mixed $flightPlan): bool => is_array($flightPlan) && is_int($flightPlan['connection_id'] ?? null))
            ->reverse()
            ->keyBy('connection_id');
    }

    /**
     * @param  array<string, int>  $query
     * @return array<mixed, mixed>
     *
     * @throws ConnectionException
     * @throws RequestException
     * @throws UnexpectedValueException
     */
    private function fetchJson(string $path, array $query = []): array
    {
        $response = Http::acceptJson()
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::REQUEST_TIMEOUT_SECONDS)
            ->withoutRedirecting()
            ->get(self::BASE_URL . $path, $query)
            ->throw();

        $data = $response->json();

        if (! $response->ok() || ! is_array($data)) {
            throw new UnexpectedValueException('Unexpected response with status ' . $response->status() . '.');
        }

        return $data;
    }

    private function isRecentPilotConnection(mixed $connection): bool
    {
        return is_array($connection)
            && is_int($connection['id'] ?? null)
            && ($connection['type'] ?? null) === self::PILOT_CONNECTION_TYPE
            && is_string($connection['callsign'] ?? null)
            && $connection['callsign'] !== ''
            && is_string($connection['start'] ?? null)
            && strtotime($connection['start']) !== false
            && Carbon::parse($connection['start'])->isAfter(now()->subDays(self::RECENT_DAYS))
            && Carbon::parse($connection['start'])->lte(now()->endOfDay());
    }

    private function icaoCode(mixed $code): ?string
    {
        return is_string($code) && preg_match('/^[A-Z0-9]{4}$/', $code) === 1 ? $code : null;
    }

    private function logFailure(string $resource, int $cid, Exception $exception): void
    {
        Log::warning('Could not load pilot ' . $resource . ' from VATSIM API', [
            'cid' => $cid,
            'message' => $exception->getMessage(),
        ]);
    }
}
