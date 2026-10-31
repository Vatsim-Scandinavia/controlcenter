<?php

namespace App\Support;

use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * Defensive dispatcher for Control Center's versioned domain events.
 *
 * Domain events are an extension point modules listen to. The default queue
 * connection is `sync`, so a listener runs in-process, inside the same request
 * or command that fired the event. A module listener that throws must never
 * break the authoritative mutation that fired the event, so every domain event
 * is dispatched through here: a throwing listener is reported and swallowed,
 * and the core operation continues.
 *
 * Listeners that need their failures to surface should handle and log their own
 * errors; by contract, anything escaping a domain-event listener is treated as
 * non-fatal to core.
 */
final class DomainEvents
{
    /**
     * Dispatch a domain event, isolating listener failures from the caller.
     */
    public static function dispatch(object $event): void
    {
        try {
            Event::dispatch($event);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
