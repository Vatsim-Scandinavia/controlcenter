<!-- markdownlint-disable first-line-heading -->

There's no Control Center without VATSIM.

Besides supporting [VATSIM Connect](./vatsim-connect.md), Control Center relies on:

 * [VATSIM API][vatsim-api]
 * [VATSIM Core API][vatsim-core-api]
 * [VATSIM ATC Bookings API][vatsim-atc-bookings-api]

!!! tip "Use VATSIM APIs with Control Center"
    See [the configuration manual](../configuration/index.md#vatsim) to get started.

## VATSIM API

[VATSIM API][vatsim-api] is used to retrieve public user data. It can be used to retrieve members of a subdivision, but it is preferred to use [VATSIM Core API][vatsim-core-api] for this task as this approach is deprecated and will be removed in the future.

!!! info
    VATSIM API key v1 is required to enable this feature. If you don't have this key already, you need to ask VATSIM Tech Department for a v2 key for Core API instead as they're not handing out v1 anymore.

## VATSIM Core API

[VATSIM Core API][vatsim-core-api] is used to retrieve members of a subdivision.

!!! info
    VATSIM Core API key v2 is required to enable this feature, contact VATSIM Tech Department using VATSIM Support to get your key.

## VATSIM ATC Bookings API

[VATSIM ATC Bookings API][vatsim-atc-bookings-api] is used get, publish, edit and remove controller bookings.

!!! info
    VATSIM ATC Bookings API key is required to enable this feature, contact VATSIM Tech Department using VATSIM Support to get your key.

[vatsim-api]: https://api.vatsim.net/api/
[vatsim-core-api]: https://vatsim.dev/api/core-api
[vatsim-atc-bookings-api]: https://atc-bookings.vatsim.net/api-doc
