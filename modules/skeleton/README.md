# Control Center module skeleton

A copy-me starting point for a Control Center module. Modules are ordinary
Composer packages of type `controlcenter-module`; see
[`docs/development/modules.md`](../../docs/development/modules.md) for the full guide.

## Use it

1. Copy this directory to `modules/<your-slug>` (or a separate repo).
2. Rename the package in `composer.json`, update the PSR-4 namespace and the
   `extra.controlcenter.module` class.
3. Set your slug, name and `ccVersion` in the manifest. All permissions, routes,
   settings and tables you add must live under `modules.<your-slug>.*`.
4. Require it from the Control Center image at build time and enable it from
   **Administration → Modules** (or `php artisan module:enable <your-slug>`).

## Views

Module views are published under the `modules::` namespace. Place a view at
`resources/views/<slug>/widget.blade.php` and reference it as
`modules::<slug>.widget`.

## Reminder

Modules are trusted, in-process code. The manifest's declared permissions and
data-handling notes are transparency for operators, not a sandbox.
