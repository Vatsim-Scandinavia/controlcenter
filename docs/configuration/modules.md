# Modules

Control Center can be extended with **modules** — packages that add
integrations, settings, panels and actions. This page is for operators running
an instance; module authors should read the
[module developer guide](../development/modules.md).

!!! warning "Modules are trusted code"
    A module runs with the **full privileges of the application**. The
    permissions and data-handling notes shown for a module are **transparency,
    not a security boundary** — an enabled module can do anything core can.
    Install only modules you trust.

## Installing modules

Modules are installed **at image build time** with Composer, not uploaded at
runtime. The standard image ships no modules; to add one, build a
[custom image](../setup/custom.md) that `require`s it.

## Enabling and disabling

Installed modules are **disabled by default**. Manage them from
**Administration → Modules** (requires the `system.modules.manage` permission),
or from the CLI:

```bash
php artisan module:list               # show installed modules and state
php artisan module:info <slug>        # manifest + declared permissions/data
php artisan module:enable <slug>
php artisan module:disable <slug>
```

Enabled state is stored in the database and takes effect immediately — a
disabled module contributes no navigation, routes, permissions, settings,
scheduled work or event listeners. Toggling works even when the config cache is
warm.

The modules page shows each module's version, status (enabled / disabled /
**incompatible**), declared permissions and the user data it handles, plus a
health summary and any boot errors.

## Settings

Each module that declares settings gets its own settings page, linked from the
modules list. Secret fields (API keys, tokens) are **encrypted at rest** and
never shown back to you — you see only whether a value is set and can replace
it.

## Permissions

Module permissions live under `modules.<slug>.*`. By default:

- **`admin`** holds all module permissions (via the catch-all `**`).
- **`director` is denied** all module permissions (`!modules.**`).
- Other roles opt in: add `modules.<slug>.*` patterns to the role in
  `config/roles.php`.

See [Roles and permissions](../reference/permissions.md).

## Safe mode

If a module makes an instance unusable, set `MODULES_SAFE_MODE=true`. No modules
are booted regardless of their stored state, so core keeps working while you
diagnose. The modules page still lists everything and shows a banner.

`MODULES_SAFE_MODE` is read from the environment, so on an instance with cached
config you must rebuild the config cache for the change to take effect — run
`php artisan config:cache` (or `php artisan config:clear` to drop it), and the
same again when turning safe mode back off. Module routes react on the next
request without any cache step, but the safe-mode flag itself lives in config.

## Background work

Modules may register scheduled commands and event listeners. The default
`QUEUE_CONNECTION` is `sync`, so module listeners run inline with the request
that triggers them; module scheduled tasks run only while the module is enabled.
Module failures are isolated, logged, and tagged in Sentry with the module slug.
Telemetry never includes module data.

## Upgrading

The modules system adds no required steps for existing installs — core features
are unchanged and no modules ship enabled. See [Upgrading](../upgrade.md) for
the release notes.
