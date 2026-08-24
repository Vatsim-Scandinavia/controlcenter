---
icon: material/api
hide:
- toc
title: API (v1)
---

<!-- markdownlint-disable MD033 -->

## Authentication

To authenticate, first create an API key via `php artisan create:apikey`, then use the returned token as the authentication bearer, e.g. as the field `<token>` in `Authentication: Bearer <token>`.

--8<-- "exec-in-container.md"

## API v1

Interactive reference for the Control Center API v1.

<swagger-ui src="api-v1.json"/>

