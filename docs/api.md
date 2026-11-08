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
  "external_url": "https://staffing.adbj.no/rosters/123"
}
```

Times are UTC. `external_id` is required, up to 128 characters, and must identify
the assignment independently of controller, position, or time. Republishing the
same identifier updates the existing booking within that integration. Identical
identifiers from different integrations are independent. `external_url` is an
optional HTTP or HTTPS management link; omit it to retain an existing link or
send `null` to remove the link.

Bookings are read-only in CC, including for their owner and staff. When supplied,
the management link directs them to the originating system. Existing `DISCORD`
bookings retain their current behavior.

Cancel removed assignments explicitly using `DELETE /api/v1/bookings/{booking}`.
Repeated deletion succeeds without republishing a cancellation. Republishing a
cancelled assignment returns `409`; use a new assignment ID to create a new
booking. There is no implicit removal of assignments omitted from a publication
and no batch endpoint.

The API allows 60 requests per minute. Respect `429` responses and `Retry-After`.
Concurrent publications of the same assignment can return `409`; retry shortly.
VATSIM errors return `400`. A stable assignment ID prevents duplicate local
bookings on successful retries, but an ambiguous upstream timeout still requires
reconciliation with VATSIM; the two systems do not share a transaction.

## API v1

Interactive reference for the Control Center API v1.

<swagger-ui src="api-v1.json"/>

