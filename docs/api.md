---
icon: material/api
hide:
- toc
title: API (v1)
---

<!-- markdownlint-disable MD033 -->

# Control Center API v1

Other applications can manage Control Center through `/api/v1` using API keys.
The interactive OpenAPI reference is at the bottom of this page.

The unversioned `/api` prefix still exposes the original bookings, users, and
positions endpoints for existing clients. New management routes exist only under
`/api/v1`.

## Authentication

Create a key with `php artisan create:apikey`, then send it as a bearer token:

```http
Authorization: Bearer <token>
```

--8<-- "exec-in-container.md"

| Key type | Access |
| --- | --- |
| Read-only | `GET` routes behind `api-token` |
| Edit | All read routes, plus every write route behind `api-token:edit` |

An edit key may perform any staff action exposed by the API. It is not tied to a
user or an area. Granting the `admin` role remains CLI-only
(`php artisan user:makeadmin`).

### Public GETs

Without a key, these paths still return a reduced payload:

- `GET /api/v1/bookings`
- `GET /api/v1/positions`

Mutating methods on those paths always require an edit key. Query parameters
such as `?full=1` on positions only apply when a key authenticates the request.

### `actor_cid`

Division API writes (mentor roles, facility/solo/examiner endorsements, and
similar) require `actor_cid`: an existing VATSIM CID in `users`. The edit key is
the authorization; `actor_cid` is only the requester sent to VATSIM and the
activity-log causer. It is not checked against that user's roles.

## Conventions

- List and show payloads use `{ "data": ... }`. Paginated lists also include
  `meta` with `current_page`, `last_page`, `per_page`, and `total`.
- Validation failures return Laravel's `422` body.
- Domain refusals return `409` with `{ "message": "..." }`.
- Division API failures return `422` with the adapter message.
- New timestamps are ISO-8601. User ids remain VATSIM CIDs.
- Paginated collections (`endorsements`) accept `page` and `per_page`
  (default 50, max 100). The original users and bookings lists stay unpaginated.

## Endpoint overview

All paths below are relative to `/api/v1`.

### Catalogue

| Method | Path | Notes |
| --- | --- | --- |
| `GET` | `/areas`, `/areas/{area}` | |
| `GET` | `/ratings` | Includes area pivot fields |
| `GET` | `/users` | Existing `include[]` / `onlyAtcActive` query |
| `GET` | `/users/{user}` | Same `include[]` values as the list |
| `GET` | `/endorsements` | Filter by `type`, `area_id`, `user_id`, `expired`, `revoked` |
| `GET` | `/positions` | Default: `callsign`, `name`, `frequency`. Use `?full=1` with a key for management fields |
| `GET` | `/positions/{position}` | Full management fields |

### Bookings and positions

| Method | Path | Notes |
| --- | --- | --- |
| `GET` | `/bookings`, `/bookings/{booking}` | |
| `POST` | `/bookings/create` | Edit key. Body uses `d/m/Y` and `H:i` |
| `PATCH` / `DELETE` | `/bookings/{booking}` | Edit key |
| `POST` / `PATCH` / `DELETE` | `/positions`… | Edit key. Same validation as the admin UI |

### Endorsements and roles

| Method | Path | Notes |
| --- | --- | --- |
| `POST` | `/endorsements` | `FACILITY`, `SOLO`, `EXAMINER`, `VISITING` |
| `DELETE` | `/endorsements/{endorsement}` | Revoke |
| `POST` | `/endorsements/{endorsement}/shorten` | Solos |
| `POST` / `DELETE` | `/users/{user}/roles` | Body: `role`, optional `area_id`, required `actor_cid`. `admin` refused |

## Regenerating the OpenAPI file

After changing v1 routes or controller annotations:

```sh
composer api-docs
```

That exports [`api-v1.json`](api-v1.json). CI checks that the committed file matches
a fresh export.

## API v1 reference

Interactive OpenAPI document for `/api/v1`.

<swagger-ui src="api-v1.json"/>
