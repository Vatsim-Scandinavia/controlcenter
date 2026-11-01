<?php

namespace ControlCenter\Modules\Skeleton;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Navigation\NavigationItem;
use ControlCenter\ModuleApi\Registrar;
use ControlCenter\ModuleApi\Settings\SettingsSchema;
use ControlCenter\ModuleApi\Ui\Slot;
use Illuminate\Support\Facades\Route;

/**
 * A minimal, working Control Center module to copy from. Every call below is
 * optional — delete what you do not need. See docs/development/modules.md for
 * the full extension-point reference.
 */
class SkeletonModule implements Module
{
    public const string SLUG = 'skeleton';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: self::SLUG,
            name: 'Skeleton',
            version: '0.1.0',
            ccVersion: '^7.0',
            description: 'Describe what your module does here.',
            author: 'Your Name',
            permissions: [
                'modules.skeleton.view' => 'View the skeleton module',
            ],
            dataHandling: [
                // Describe any user data you store so operators and the
                // privacy flow know about it, e.g.:
                // 'Stores a preference row per user keyed by CID.',
            ],
            // If you store user data, declare a privacy handler here (not in
            // register()), so it is available even while the module is disabled:
            // privacyHandler: \ControlCenter\Modules\Skeleton\SkeletonPrivacy::class,
        );
    }

    public function register(Registrar $r): void
    {
        $r->permission('modules.skeleton.view', 'View the skeleton module')
            ->navigation(new NavigationItem(
                title: 'Skeleton',
                route: 'modules.skeleton.index',
                permission: 'modules.skeleton.view',
                icon: 'fa-cube',
            ))
            ->slot(Slot::DashboardWidgets, 'modules::skeleton.widget', 'modules.skeleton.view')
            ->adminSettings(
                (new SettingsSchema)
                    ->text('welcome_message', 'Welcome message', ['nullable', 'string', 'max:255'])
                    ->secret('api_key', 'Upstream API key', ['nullable', 'string'])
            )
            ->web(function (): void {
                Route::get('/', fn () => view('modules::skeleton.index'))->name('index');
            });
    }
}
