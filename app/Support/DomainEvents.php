<?php

namespace App\Support;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Testing\Fakes\EventFake;
use Throwable;

/**
 * Defensive dispatcher for Control Center's versioned domain events.
 *
 * Domain events are an extension point modules listen to. The default queue
 * connection is `sync`, so a listener runs in-process, inside the same request
 * or command that fired the event. Each listener is dispatched in isolation so
 * that one listener throwing neither breaks the authoritative mutation that
 * fired the event nor suppresses the other listeners.
 *
 * Listeners that need their failures to surface should handle and log their own
 * errors; by contract, anything escaping a domain-event listener is treated as
 * non-fatal to core.
 */
final class DomainEvents
{
    /**
     * Dispatch a domain event, isolating each listener individually.
     */
    public static function dispatch(object $event): void
    {
        $dispatcher = Event::getFacadeRoot();

        // Under Event::fake() the events are only recorded for assertions; let
        // the fake handle it so assertDispatched() keeps working.
        if ($dispatcher instanceof EventFake) {
            $dispatcher->dispatch($event);

            return;
        }

        // Invoke each listener (including wildcard listeners, which
        // getListeners() merges in) in its own try/catch.
        foreach ($dispatcher->getListeners($event::class) as $listener) {
            try {
                $listener($event::class, [$event]);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
