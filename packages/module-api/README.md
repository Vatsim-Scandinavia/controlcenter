# Control Center Module API

Stable contracts and value objects for building [Control Center](https://github.com/Vatsim-Scandinavia/controlcenter)
modules. Depend only on the `ControlCenter\ModuleApi\` namespace — see
[`SEMVER.md`](SEMVER.md) for the versioning promise.

A module is an ordinary Composer package of type `controlcenter-module` whose
`composer.json` points at the class implementing `ControlCenter\ModuleApi\Module`:

```json
{
    "name": "acme/cc-widget",
    "type": "controlcenter-module",
    "require": { "controlcenter/module-api": "^1.0" },
    "extra": { "controlcenter": { "module": "Acme\\Widget\\WidgetModule" } }
}
```

```php
use ControlCenter\ModuleApi\{Module, ModuleManifest, Registrar};
use ControlCenter\ModuleApi\Navigation\NavigationItem;

class WidgetModule implements Module
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: 'widget',
            name: 'Acme Widget',
            version: '1.0.0',
            ccVersion: '^7.0',
            description: 'Adds a widget.',
            permissions: ['modules.widget.view' => 'View the widget'],
        );
    }

    public function register(Registrar $r): void
    {
        $r->permission('modules.widget.view', 'View the widget')
          ->navigation(new NavigationItem('Widget', 'modules.widget.index', 'modules.widget.view', 'fa-plug'));
    }
}
```

Modules are **trusted in-process code**. The manifest's declared permissions and
data-handling notes are transparency for operators, not a sandbox: a module runs
with the application's full privileges.

See the Control Center `docs/` module developer guide for the full extension
point reference and the module skeleton under `modules/skeleton`.
