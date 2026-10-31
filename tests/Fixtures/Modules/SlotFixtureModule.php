<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;
use ControlCenter\ModuleApi\Ui\Slot;

/**
 * Fixture module used to exercise the module UI slot component: one visible
 * widget, one that throws (to prove error isolation) and one gated behind a
 * permission.
 */
class SlotFixtureModule implements Module
{
    public const string SLUG = 'slotfix';

    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: self::SLUG,
            name: 'Slot Fixture',
            version: '1.0.0',
            ccVersion: '*',
            description: 'Fixture module for slot rendering tests.',
            author: 'Control Center',
            permissions: [
                'modules.slotfix.view' => 'View the slot fixture',
            ],
            dataHandling: [],
        );
    }

    public function register(Registrar $r): void
    {
        $r->permission('modules.slotfix.view', 'View the slot fixture')
            ->slot(Slot::DashboardWidgets, 'slotfix::ok')
            ->slot(Slot::DashboardWidgets, 'slotfix::boom')
            ->slot(Slot::UserPanels, 'slotfix::panel', 'modules.slotfix.view');
    }
}
