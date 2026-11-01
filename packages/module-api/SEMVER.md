# Module API versioning policy

`controlcenter/module-api` is the stable contract between Control Center core
and the modules operators install. It follows [Semantic Versioning](https://semver.org/).

!!! note "Pre-1.0"
    The package is still pre-1.0 and unreleased; the surface may change between
    development builds without a major bump. Recent shifts while it settles: the
    privacy handler is now declared on `ModuleManifest` (not via a `Registrar`
    method); `Registrar::bind()` was removed; `Registrar::schedule()` now takes
    a command class plus a timing closure; and `Registrar::driver()` is limited
    to an allow-listed contract. Once tagged `1.0.0`, the promise below applies.

## The promise

Everything under `ControlCenter\ModuleApi\` is public API: the `Module` and
`Registrar` interfaces, the `ModuleManifest`, value objects (`NavigationItem`,
`SettingsSchema`, `SettingField`), the `Slot` constants and the
`PrivacyHandler` contract.

- **MAJOR** — a change that can break a module compiled against the previous
  major: removing or renaming a method, adding a required method to an
  interface, changing a parameter or return type incompatibly, removing a slot
  constant or enum case.
- **MINOR** — backwards-compatible additions: a new optional registration
  method with a default implementation in core, a new value object, a new slot
  constant, new optional constructor parameters with defaults.
- **PATCH** — documentation, internal fixes and clarifications that do not
  change the surface.

## Interplay with the Control Center version

A module's `ModuleManifest::$ccVersion` is a Composer constraint checked at boot
against `config('app.version')`. The module API version and the Control Center
application version move independently:

- The module API major version changes only when the contract breaks.
- `$ccVersion` lets a module state which Control Center releases it supports
  (e.g. `^7.0`). An incompatible module is discovered but never booted, and the
  admin page explains why.

## What is *not* covered

Core internals (`App\Modules\*`), the concrete `Registrar` implementation, the
database schema of the `modules` table, and task-type base classes are
implementation details and may change in any release. Modules must depend only
on `ControlCenter\ModuleApi\`.
