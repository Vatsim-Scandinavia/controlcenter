---
icon: material/api
hide:
- toc
title: API (v1)
---

<!-- markdownlint-disable MD033 -->

## Authentication

To authenticate, first create an API key via `php artisan create:apikey`, then use the returned token as the authentication bearer, e.g. as the field `<token>` in `Authorization: Bearer <token>`.

--8<-- "exec-in-container.md"

## Externally managed bookings

Roster and staffing systems use the shared `EXTERNAL` booking source. Create a
writable API key with a stable integration identifier:

```shell
php artisan create:apikey --integration=staffing-adbj
```

Choose edit rights when prompted. Reuse the same integration identifier when
rotating credentials. Integration keys can only create `EXTERNAL` bookings and
can only update or cancel bookings belonging to that integration. Existing
general-purpose API keys cannot modify `EXTERNAL` bookings.

Integration keys can read only their own bookings and public positions; they
cannot access the users endpoint or another integration's booking details.
General-purpose keys retain their existing read access.

Rolling back the external-booking migration revokes all integration keys before
removing their integration identifiers. This includes read-only integration keys,
because older API code cannot preserve their restricted read access. General-purpose
keys are retained. If the feature is subsequently reinstalled, provision new
integration credentials; revoked tokens are not restored.

Publish each assignment to `POST /api/v1/bookings/create` using the bearer token:

```json
{
  "cid": 10000005,
  "date": "15/12/2026",
  "start_at": "18:00",
  "end_at": "20:00",
  "position": "EKCH_TWR",
  "tag": 3,
  "source": "EXTERNAL",
  "external_id": "event-123-assignment-456",
  "external_revision": 1,
  "external_url": "https://staffing.adbj.no/rosters/123"
}
```

Times are UTC. `external_id` is required, up to 128 characters, and must identify
the assignment independently of controller, position, or time. Republishing the
same identifier updates the existing booking within that integration. Identical
identifiers from different integrations are independent. Assignment identifiers
are case-sensitive, so `assignment-A` and `assignment-a` are different assignments.
`external_url` is an optional HTTP or HTTPS management link; omit it to retain an existing link or
send `null` to remove the link.

External publications and PATCH updates require `external_revision`, an integer
from 1 to 2147483647 that increases with each change to an assignment. Older
revisions return `409` without publishing to VATSIM. Repeating an accepted revision
returns the stored booking without changing it or contacting VATSIM; use a higher
revision whenever the payload changes. The integration must persist its revision
counter across retries and restarts.

Bookings are read-only in CC, including for their owner and staff. When supplied,
the management link directs them to the originating system. Existing `DISCORD`
bookings retain their current behavior.

Cancel removed assignments explicitly using `DELETE /api/v1/bookings/{booking}`.
For external bookings, send `{"external_revision": 2}` in the request body, using
a revision higher than the last accepted publication or update. Stale cancellations
return `409` and cannot cancel a newer publication.
Repeated deletion succeeds without republishing a cancellation. Republishing a
cancelled assignment returns `409`; use a new assignment ID to create a new
booking. There is no implicit removal of assignments omitted from a publication
and no batch endpoint.

The API allows 60 requests per minute. Respect `429` responses and `Retry-After`.
Concurrent publications of the same assignment can return `409`; retry shortly.
The same applies when a booking or position is being changed. Multi-instance
deployments must use a shared cache supporting atomic locks to serialize competing
mutations across instances. Cancelled assignments and stale revisions also return `409`;
retrying those requests unchanged will not resolve the conflict.
VATSIM errors return `400`. A stable assignment ID prevents duplicate local
bookings on successful retries, but an ambiguous upstream timeout still requires
reconciliation with VATSIM; the two systems do not share a transaction.

## API v1

Interactive reference for the Control Center API v1.

<swagger-ui src="api-v1.json"/>

