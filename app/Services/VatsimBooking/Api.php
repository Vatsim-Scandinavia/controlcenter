<?php

namespace App\Services\VatsimBooking;

use App\Contracts\VatsimBookingApiContract;
use App\Exceptions\VatsimAPIException;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Publishes bookings to the central VATSIM booking API.
 */
class Api implements VatsimBookingApiContract
{
    public function createBooking(Booking $booking, string $type): ?int
    {
        $response = $this->callApi('post', $this->bookingUrl(), $this->bookingPayload($booking, $type));

        return $this->bookingId($response);
    }

    public function updateBooking(Booking $booking, string $type): ?int
    {
        $response = $this->callApi('put', $this->bookingUrl($booking->vatsim_booking), $this->bookingPayload($booking, $type));

        return $this->bookingId($response);
    }

    /**
     * Extract the VATSIM booking id, or null if the response lacks a valid positive integer id.
     */
    private function bookingId(Response $response): ?int
    {
        $id = filter_var($response->json('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    public function deleteBooking(Booking $booking): void
    {
        $this->callApi('delete', $this->bookingUrl($booking->vatsim_booking));
    }

    /**
     * @return array{callsign: string, cid: int, type: string, start: string, end: string}
     */
    private function bookingPayload(Booking $booking, string $type): array
    {
        return [
            'callsign' => (string) $booking->callsign,
            'cid' => $booking->user_id,
            'type' => $type,
            'start' => Carbon::parse($booking->time_start)->format('Y-m-d H:i:s'),
            'end' => Carbon::parse($booking->time_end)->format('Y-m-d H:i:s'),
        ];
    }

    private function bookingUrl(?int $vatsimBookingId = null): string
    {
        return config('vatsim.booking_api_url') . '/booking' . ($vatsimBookingId !== null ? '/' . $vatsimBookingId : '');
    }

    /**
     * @throws VatsimAPIException
     */
    private function callApi(string $method, string $url, ?array $data = null): Response
    {
        try {
            $response = Http::withToken(config('vatsim.booking_api_token'))
                ->acceptJson()
                ->asForm()
                ->$method($url, $data ?? []);
        } catch (ConnectionException $e) {
            throw new VatsimAPIException('Could not connect to the VATSIM booking API', 503, $e);
        }

        if ($response->failed()) {
            throw new VatsimAPIException('VATSIM booking API responded with status ' . $response->status(), $response->status());
        }

        return $response;
    }
}
