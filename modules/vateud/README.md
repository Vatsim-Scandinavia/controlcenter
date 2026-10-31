# VATEUD module

VATEUD division API integration for Control Center, packaged as a first-party
module. It provides the `vateud` driver for the core division API contract, the
roster and endorsement sync commands (`vateud:sync-roster`,
`vateud:sync-endorsements`) and their schedule, which runs only while the module
is enabled.

## Settings

- `modules.vateud.api_url` — VATEUD API base URL.
- `modules.vateud.api_key` — VATEUD API secret, stored encrypted.

The legacy core `divisionApiEnabled` setting and `VATSIM_DIVISION_API_*`
environment are migrated into this module on upgrade; enabling the module makes
its driver the active division API.
