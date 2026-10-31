<?php

namespace ControlCenter\Modules\Moodle;

use anlutro\LaravelSettings\Facade as Setting;
use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Navigation\NavigationItem;
use ControlCenter\ModuleApi\Registrar;
use ControlCenter\ModuleApi\Settings\SettingsSchema;
use Illuminate\Support\Facades\Route;

/**
 * First-party module exposing a sidebar link to an external Moodle instance.
 *
 * The Moodle URL used to be a core "linkMoodle" global setting; it now lives in
 * this module's `modules.moodle.url` setting. The sidebar link is only shown
 * while the module is enabled and a URL has been configured.
 */
class MoodleModule implements Module
{
    public const string SLUG = 'moodle';

    public const string URL_KEY = 'modules.moodle.url';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: self::SLUG,
            name: 'Moodle',
            version: '1.0.0',
            ccVersion: '^7.0',
            description: 'Adds a sidebar link to your division Moodle instance.',
            author: 'Control Center',
            permissions: [],
            dataHandling: [],
        );
    }

    public function register(Registrar $r): void
    {
        $r->adminSettings(
            (new SettingsSchema)->text(
                'url',
                'Moodle URL',
                ['nullable', 'url'],
                'Full link to your Moodle instance. Shown as a sidebar link to all users. Leave blank to hide the link.',
            )
        )->web(function (): void {
            Route::get('/', function () {
                $url = Setting::get(self::URL_KEY);

                return ($url && $url !== '')
                    ? redirect()->away($url)
                    : redirect()->route('dashboard');
            })->name('index');
        });

        // Read defensively: during early boot (before migrations) the settings
        // table may not exist yet. rescue() keeps registration from throwing.
        $url = rescue(fn () => Setting::get(self::URL_KEY), null, false);

        if ($url && $url !== '') {
            $r->navigation(new NavigationItem(
                title: 'Moodle',
                route: 'modules.moodle.index',
                icon: 'fa-graduation-cap',
                order: 10,
            ));
        }
    }
}
