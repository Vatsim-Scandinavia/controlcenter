# Module developer guide

Control Center can be extended with **modules**: independently maintained
packages that add integrations, settings, panels and actions inside Control
Center's interface, while core stays focused on shared features and a small set
of stable extension points.

!!! warning "Modules are trusted, in-process code"
    A module runs with the **full privileges of the application** — the same
    database, filesystem and network access as core. The permissions and
    data-handling notes a module declares in its manifest are **transparency for
    operators, not a sandbox**. Only install modules you trust, exactly as you
    would any Composer dependency.

## What a module is

A module is an ordinary Composer package of type `controlcenter-module`. Its
`composer.json` names the class implementing
`ControlCenter\ModuleApi\Module`:

```json
{
    "name": "acme/cc-widget",
    "type": "controlcenter-module",
    "require": { "controlcenter/module-api": "^1.0" },
    "autoload": { "psr-4": { "Acme\\Widget\\": "src/" } },
    "extra": { "controlcenter": { "module": "Acme\\Widget\\WidgetModule" } }
}
```

Modules are **installed at image build time** (via Composer) and **enabled or
disabled at runtime** from **Administration → Modules** (or `php artisan
module:enable <slug>`). There is no runtime upload.

Depend only on the `controlcenter/module-api` package — see its
[`SEMVER.md`](https://github.com/Vatsim-Scandinavia/controlcenter/blob/main/packages/module-api/SEMVER.md)
for the compatibility promise. Core internals (`App\Modules\*`) are not part of
the contract.

## The `Module` class

```php
use ControlCenter\ModuleApi\{Module, ModuleManifest, Registrar};

class WidgetModule implements Module
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: 'widget',                 // lowercase kebab-case, unique
            name: 'Acme Widget',
            version: '1.0.0',
            ccVersion: '^7.0',              // Control Center versions supported
            description: 'Adds a widget.',
            author: 'Acme',
            permissions: ['modules.widget.view' => 'View the widget'],
            dataHandling: ['Stores one widget preference row per user (CID).'],
        );
    }

    public function register(Registrar $r): void { /* extension points */ }
}
```

`manifest()` is static metadata read during discovery. `register()` is normally
called once per request while the module is enabled and compatible; keep it
cheap and side-effect free — it runs on nearly every page. One flow is the
exception: `user:delete` (and other privacy actions) resolve the registrar of
**every installed module, enabled or not**, so a disabled module's privacy
handlers still run against a departing user's data. `register()` must therefore
be safe to call even when the module is disabled, and must not assume it only
runs while enabled.

### Compatibility

`ccVersion` is a Composer version constraint checked at boot against
`config('app.version')`. An incompatible module is discovered but never booted,
and the admin page explains why. Pre-release suffixes (e.g. `7.0.0-beta.4`) are
normalised before comparison.

## Namespacing

Everything a module registers that could clash with core **must live under
`modules.<slug>.*`**: permissions, roles' grants, API scopes and settings keys.
Registering outside your namespace throws. Module tables use the
`modules_<slug>_` prefix, and modules must never alter core tables.

## The `Registrar` — extension points

`register(Registrar $r)` is the single stable surface. All methods are fluent.

### Navigation

```php
use ControlCenter\ModuleApi\Navigation\NavigationItem;

$r->navigation(new NavigationItem(
    title: 'Widget', route: 'modules.widget.index',
    permission: 'modules.widget.view', icon: 'fa-plug', order: 100,
));
```

Items render in the sidebar, authorized through the gate by `permission`
(null = any authenticated user) and hidden otherwise.

### UI slots

Render a Blade view into a named slot. Slots are authorized per item and
**error-isolated** — a slot that throws is reported and skipped, never breaking
the page.

```php
use ControlCenter\ModuleApi\Ui\Slot;

$r->slot(Slot::DashboardWidgets, 'widget::dashboard', 'modules.widget.view')
  ->slot(Slot::UserPanels, 'widget::user-panel')
  ->slot(Slot::Head, 'widget::head')      // for <link>/<script> tags
  ->slot(Slot::Scripts, 'widget::scripts');
```

Stable slots: `dashboard.widgets`, `user.panels`, `training.panels`,
`training.actions`, `admin.settings`, `user.settings`, `head`, `scripts`.

Register your views under a namespace (e.g. `widget::`) from your module's
service provider or `register()`.

### Settings

Declare a schema; **core renders the form, validates and persists it** under
`modules.<slug>.<key>`. You never write a settings form.

```php
use ControlCenter\ModuleApi\Settings\{SettingsSchema, SettingField};

$r->adminSettings(
    (new SettingsSchema)
        ->text('url', 'Service URL', ['nullable', 'url'])
        ->secret('api_key', 'API key', ['nullable', 'string'])   // encrypted
        ->boolean('enabled_flag', 'Enable feature')
);
$r->userSettings((new SettingsSchema)->text('nickname', 'Nickname'));
```

`secret` fields are **encrypted at rest** and never echoed back to the form (the
form shows only whether a value is set) or written to logs. Read values via the
`App\Modules\ModuleSettings` helper, which transparently decrypts secrets.

### UI kit

Build module surfaces with the stable `x-cc.*` Blade components so they match
Control Center's look in light and dark themes: `x-cc.card`, `x-cc.table`,
`x-cc.form`, `x-cc.input`, `x-cc.select`, `x-cc.textarea`, `x-cc.checkbox`,
`x-cc.secret`, `x-cc.alert`.

### Permissions and roles

```php
$r->permission('modules.widget.view', 'View the widget')
  ->role('reviewer', ['name' => 'Widget Reviewer', 'scope' => 'global'],
         ['modules.widget.view']);
```

Module permissions are **denied to `director` by default** (`!modules.**`);
`admin` keeps the catch-all `**`. Operators opt a role in by adding
`modules.<slug>.*` patterns to that role in `config/roles.php`.

### Task types

```php
$r->taskType('widget.review', \Acme\Widget\Tasks\ReviewTask::class);
```

The class extends Control Center's task-type base. Tasks store the stable key;
if a module is disabled, existing tasks degrade to a safe placeholder.

### Events

Core fires versioned domain events (namespace `App\Events\V1`) that modules may
listen to: `TrainingCreated`, `TrainingStatusChanged`, `TrainingReportCreated`,
`ExaminationRecorded`, `EndorsementGranted`, `EndorsementRevoked`,
`BookingCreated`, `BookingDeleted`, `RoleGranted`, `RoleRevoked`,
`FeedbackSubmitted`, `TaskCreated`, `TaskStatusChanged`, `VoteCreated`,
`VoteClosed`. A breaking change to an event's shape introduces a `V2` alongside
`V1`. Register listeners from your module; keep them resilient — the default
queue connection is `sync`, so a throwing listener runs inline.

### Drivers

Register a driver for a pluggable core contract (e.g. the division API, split
into `RosterApi`, `EndorsementApi`, `ExamApi`, `TrainingApi`). Core ships
built-in drivers via `config('vatsim.division_api_drivers')`; a module can add
its own under a name selected by `vatsim.division_api_driver`:

```php
use App\Contracts\DivisionApiContract;

$r->driver(DivisionApiContract::class, 'acme', fn ($app) => new AcmeDivisionAdapter(...));
```

### Activity log

```php
$r->activityLogName('modules.widget', 'Widget');
```

Subject models may implement `App\Contracts\ResolvesActivitySubjectRoute` to
link log entries to their detail page.

### API scopes and routes

```php
$r->apiScope('modules.widget.read', 'Read widget data')
  ->api(function () {
      Route::get('/items', [WidgetApiController::class, 'index'])
          ->middleware('api-token:modules.widget.read')->name('items');
  });
```

API routes are grouped under `/api/modules/<slug>`, named
`api.modules.<slug>.*`, guarded by the enabled-check, and kept **out of the
public OpenAPI spec**. Existing API keys keep working unchanged; scopes are
additive.

### Web routes

```php
$r->web(function () {
    Route::get('/', [WidgetController::class, 'index'])->name('index');
});
```

Grouped under `/modules/<slug>`, named `modules.<slug>.*`, with the
`['auth','activity','suspended']` middleware stack and a `module:<slug>`
enabled-check that returns 404 while the module is disabled.

Module routes are registered at the start of every HTTP request (by a global
middleware), reflecting the live enabled state, and are **never written into the
route cache**. This means `php artisan route:cache` / `optimize` is safe to run
and never needs re-running after enabling or disabling a module, and the change
takes effect on the next request across every replica. The trade-off is that
module routes are HTTP-only: they do not appear in `php artisan route:list` or
to other console commands. If a module route callback throws, that module's
routes are discarded for the request and the failure is logged; core routes and
other modules are unaffected.

### Commands and scheduling

```php
$r->command(\Acme\Widget\Commands\SyncWidget::class)
  ->schedule(fn ($schedule) => $schedule->command('widget:sync')->hourly());
```

Scheduled work only runs while the module is enabled.

### Privacy

If you store user data, declare it in `dataHandling` and register a handler.
`user:delete` invokes the handler of **every installed module (enabled or
not)** because the data still exists.

```php
use ControlCenter\ModuleApi\Privacy\PrivacyHandler;

class WidgetPrivacy implements PrivacyHandler
{
    public function describe(): string { return 'Widget preference per user.'; }
    public function export(int $cid): array { /* ... */ }
    public function pseudonymise(int $cid): void { /* ... */ }
    public function delete(int $cid): void { /* ... */ }
}
// in register(): $r->privacyHandler(WidgetPrivacy::class);
```

### Migrations

Ship migrations in your module's `database/migrations` directory; they are
discovered by `php artisan migrate`. Both layouts are supported: an in-repo
module at `modules/<slug>/database/migrations`, and a Composer-installed package,
whose migrations are discovered relative to its install path in `vendor/`
(`<install-path>/database/migrations`). Create only `modules_<slug>_*` tables;
never alter core tables.

### Frontend assets

Ship prebuilt assets (the runtime image has no Node). Emit them from the `head`
/ `scripts` slots with the `<x-cc.module-assets>` helper. The image build scans
`modules/**/resources/views` so Tailwind classes used there are not purged.

## Testing

A fixture module exercising every extension point lives at
`tests/Fixtures/Modules/ExampleModule.php`, and the `InteractsWithModules` test
trait provides `bootModules([Module::class], ['slug'])` to enable modules inside
a test. Copy the skeleton at `modules/skeleton` to start a new module.

## Bundling into the image

A module kept in-repo lives under `modules/<slug>` and is wired as a Composer
[path repository](https://getcomposer.org/doc/05-repositories.md#path) — the root
`composer.json` already declares `"url": "modules/*"` — then `require`d there. A
module published to a registry is `require`d normally. Either way it is installed
at build time; see [Creating a custom image](../setup/custom.md).
