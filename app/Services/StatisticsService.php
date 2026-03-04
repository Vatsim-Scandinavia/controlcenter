<?php

namespace App\Services;

use App\Exceptions\StatisticsApiException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StatisticsService
{
    protected string $baseUrl;

    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('vatsim.statsim_api_url'), '/');
        $this->apiKey = config('vatsim.statsim_api_key');
    }

    /**
     * Fetch ATC sessions from statistics API
     */
    public function getAtcSessions(string $vatsimId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $url = $this->baseUrl . '/api/Atcsessions/VatsimId';

        $headers = ['Accept' => 'application/json'];
        if ($this->apiKey) {
            $headers['X-API-Key'] = $this->apiKey;
        } elseif (config('app.env') === 'production') {
            Log::warning('Statistics API key not configured - API calls may fail');
        }

        try {
            $response = Http::withHeaders($headers)
                ->get($url, [
                    'vatsimId' => $vatsimId,
                    'from' => Carbon::instance($from)->setTimezone('UTC')->toIso8601String(),
                    'to' => Carbon::instance($to)->setTimezone('UTC')->toIso8601String(),
                ]);

            if (! $response->successful()) {
                Log::error('Statistics API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'vatsimId' => $vatsimId,
                ]);

                throw new StatisticsApiException(
                    'Failed to load ATC activity data from Statistics API',
                    $response->status()
                );
            }

            $sessions = $response->json();

            return is_array($sessions) ? $sessions : [];
        } catch (StatisticsApiException $e) {
            // Re-throw our custom exception
            throw $e;
        } catch (\Exception $e) {
            Log::error('Statistics API exception', [
                'message' => $e->getMessage(),
                'vatsimId' => $vatsimId,
            ]);

            throw new StatisticsApiException(
                'Failed to load ATC activity data from Statistics API',
                0,
                $e
            );
        }
    }

    /**
     * Fetch ATC sessions with caching so multiple consumers
     * (charts, tables, etc.) reuse the same StatSim response.
     */
    public function getCachedAtcSessions(string $vatsimId, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $fromKey = Carbon::instance($from)->setTimezone('UTC')->toDateString();
        $toKey = Carbon::instance($to)->setTimezone('UTC')->toDateString();

        $cacheKey = sprintf('statsim:sessions:%s:%s:%s', $vatsimId, $fromKey, $toKey);

        $ttlMinutes = (int) config('vatsim.statsim_cache_ttl_minutes', 30);
        if ($ttlMinutes <= 0) {
            // No caching configured, fall back to direct call.
            return $this->getAtcSessions($vatsimId, $from, $to);
        }

        return Cache::remember(
            $cacheKey,
            now()->addMinutes($ttlMinutes),
            fn () => $this->getAtcSessions($vatsimId, $from, $to)
        );
    }

    /**
     * Transform statistics API response to match old API format for backward compatibility
     */
    public function transformSessions(array $sessions): array
    {
        if (empty($sessions)) {
            return [];
        }

        $transformed = [];

        foreach ($sessions as $session) {
            if (! is_array($session)) {
                continue;
            }

            $logonTime = $this->parseTimestamp($session['loggedOn'] ?? null);
            if ($logonTime === null) {
                continue;
            }

            $logoffTime = $this->parseTimestamp($session['loggedOff'] ?? null) ?? $logonTime;

            $transformed[] = [
                'callsign' => $session['callsign'] ?? '',
                'logontime' => $logonTime,
                'logofftime' => $logoffTime,
            ];
        }

        return $transformed;
    }

    /**
     * Build a compact \"recent sessions\" summary suitable for table display,
     * based on a cached StatSim response.
     *
     * This reuses the same underlying dataset that powers the activity chart.
     */
    public function getRecentSessionsSummary(
        string $vatsimId,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        ?int $limit = null,
        ?int $days = null
    ): array {
        $limit ??= (int) config('vatsim.recent_sessions_limit', 10);
        $days ??= (int) config('vatsim.recent_sessions_days', 30);

        try {
            $sessions = $this->getCachedAtcSessions($vatsimId, $from, $to);
        } catch (StatisticsApiException $e) {
            // Bubble up as an empty table if the API fails.
            return [];
        }

        $transformed = $this->transformSessions($sessions);

        // Optionally restrict to the last N days within the provided window.
        if ($days > 0) {
            $cutoffTimestamp = Carbon::instance($to)->setTimezone('UTC')->subDays($days)->timestamp;

            $transformed = array_filter($transformed, static function (array $session) use ($cutoffTimestamp): bool {
                return isset($session['logontime']) && $session['logontime'] >= $cutoffTimestamp;
            });
        }

        // Sort by most recent first.
        usort($transformed, static function (array $a, array $b): int {
            return ($b['logontime'] ?? 0) <=> ($a['logontime'] ?? 0);
        });

        // Take only the most recent sessions we care about for the table.
        $transformed = array_slice($transformed, 0, max($limit, 0));

        $summary = [];

        foreach ($transformed as $session) {
            $logonTimestamp = $session['logontime'] ?? null;
            $logoffTimestamp = $session['logofftime'] ?? null;

            if ($logonTimestamp === null || $logoffTimestamp === null) {
                continue;
            }

            $logon = Carbon::createFromTimestamp($logonTimestamp, 'UTC');
            $logoff = Carbon::createFromTimestamp($logoffTimestamp, 'UTC');

            $totalMinutes = $logon->diffInMinutes($logoff);
            $hours = (int) floor($totalMinutes / 60);
            $minutes = $totalMinutes % 60;

            $duration = $hours > 0
                ? sprintf('%dh %02dm', $hours, $minutes)
                : sprintf('%dm', $minutes);

            $summary[] = [
                'callsign' => $session['callsign'] !== '' ? $session['callsign'] : '—',
                'start' => $logon->toIso8601String(),
                'duration' => $duration,
            ];
        }

        return $summary;
    }

    /**
     * Parse ISO 8601 date string to Unix timestamp
     */
    protected function parseTimestamp(?string $dateTime): ?int
    {
        if (! $dateTime) {
            return null;
        }

        try {
            return Carbon::parse($dateTime)->timestamp;
        } catch (\Exception $e) {
            // Fallback to strtotime for edge cases
            $timestamp = strtotime($dateTime);
            if ($timestamp === false) {
                Log::warning('Failed to parse statistics date', [
                    'date' => $dateTime,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }

            return $timestamp;
        }
    }
}
