<!-- markdownlint-disable first-line-heading -->

Roster and staffing systems can publish bookings into Control Center using the
shared `EXTERNAL` booking source. These bookings are read-only in Control Center
and are managed entirely by the system that published them.

## Creating an integration key

Create a writable API key with a stable integration identifier:

```shell
php artisan create:apikey --integration=INTEGRATION_IDENTIFIER
```

--8<-- "exec-in-container.md"

Choose edit rights when prompted. Integration identifiers contain 1–64 lowercase
letters, numbers, underscores or hyphens, and start with a letter or number.
Reuse the same identifier when rotating credentials.

Integration keys can only create `EXTERNAL` bookings, and can only update or
cancel bookings belonging to that integration. They can read only their own
bookings and public positions; they cannot access the users endpoint or another
integration's booking details. Existing general-purpose API keys keep their read
access but cannot modify `EXTERNAL` bookings.

## Publishing assignments

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
  "external_url": "https://roster.example.com/rosters/123"
}
```

Times are UTC. `external_id` is required, up to 128 characters, and must identify
the assignment independently of controller, position, or time. Republishing the
same identifier updates the existing booking within that integration. Identical
identifiers from different integrations are independent. Assignment identifiers
are case-sensitive, so `assignment-A` and `assignment-a` are different assignments.

`external_url` is an optional HTTP or HTTPS management link. Omit it to retain an
existing link or send `null` to remove the link. When supplied, the booking's icon
in Control Center links members and staff to the originating system.

## Revisions

External publications and PATCH updates require `external_revision`, a positive
integer that increases with each change to an assignment. Older revisions return
`409` without publishing to VATSIM. Repeating an accepted revision returns the
stored booking without changing it or contacting VATSIM; use a higher revision
whenever the payload changes. The integration must persist its revision counter
across retries and restarts.

## Cancelling assignments

Cancel removed assignments explicitly using `DELETE /api/v1/bookings/{booking}`,
sending `{"external_revision": 2}` in the request body with a revision higher than
the last accepted publication or update. Stale cancellations return `409` and
cannot cancel a newer publication. Repeated deletion succeeds without sending
another cancellation to VATSIM.

Republishing a cancelled assignment returns `409`; use a new assignment ID to
create a new booking. Assignments omitted from a publication are not removed
implicitly, and there is no batch endpoint.

## Errors and retries

The API allows 60 requests per minute. Respect `429` responses and `Retry-After`.

Concurrent changes to the same assignment, booking or position can return `409`;
retry shortly. Cancelled assignments and stale revisions also return `409`, but
retrying those requests unchanged will not resolve the conflict. VATSIM errors
return `400`.

A stable assignment ID prevents duplicate local bookings on successful retries,
but an ambiguous upstream timeout still requires reconciliation with VATSIM; the
two systems do not share a transaction.

!!! warning "Multiple application instances"
    Deployments running several instances must use a shared cache supporting
    atomic locks, so competing mutations are serialized across instances.
