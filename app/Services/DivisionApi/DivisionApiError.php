<?php

namespace App\Services\DivisionApi;

use App\Facades\DivisionApi;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

/**
 * Turns a failed Division API call into text we can show a user.
 *
 * The API does not always answer with the JSON envelope we expect. A gateway
 * can return an HTML error page, a rate limiter can return an empty body, and
 * an outage can mean no response at all. Reading $response->json()['message']
 * straight off the response in those cases leaves the reader with an error
 * that explains nothing, precisely when something has gone badly wrong.
 */
final class DivisionApiError
{
    /**
     * Longest raw body we inline before truncating. These strings end up in
     * flash messages and console output, and an HTML error page is kilobytes.
     */
    private const MAX_BODY_LENGTH = 200;

    /**
     * The standard sentence shown when a Division API call fails.
     */
    public static function message(?Response $response): string
    {
        return 'Request failed due to error in ' . DivisionApi::getName() . ' API: ' . self::detail($response);
    }

    /**
     * The API's own explanation of the failure, falling back to the status and
     * body when the response is missing or is not the JSON we expect.
     */
    public static function detail(?Response $response): string
    {
        if ($response === null) {
            return 'no response received.';
        }

        $message = $response->json('message');
        if (is_string($message) && trim($message) !== '') {
            return $message;
        }

        // Anything else (absent key, validation-error array, non-JSON body) is
        // reported through the raw body, which still carries the detail.
        $body = trim($response->body());
        if ($body === '') {
            return 'HTTP ' . $response->status() . ' with an empty body.';
        }

        return 'HTTP ' . $response->status() . ': ' . Str::limit($body, self::MAX_BODY_LENGTH);
    }
}
