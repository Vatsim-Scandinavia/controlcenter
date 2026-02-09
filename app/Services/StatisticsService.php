<?php

namespace App\Services;

use App\Exceptions\StatisticsApiException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StatisticsService
{
    protected string $baseUrl;

    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('vatsim.stats_api_url', 'https://api.statsim.net/'), '/');
        $this->apiKey = config('vatsim.stats_api_key');
    }

    /**
     * Fetch ATC sessions from statistics API
     *
     * @param  string  $from  ISO 8601 date string
     * @param  string  $to  ISO 8601 date string
     */
    public function getAtcSessions(string $vatsimId, string $from, string $to): array
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
                    'from' => $from,
                    'to' => $to,
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
