<?php

namespace Tests\Fixtures\Modules;

use ControlCenter\ModuleApi\Module;
use ControlCenter\ModuleApi\ModuleManifest;
use ControlCenter\ModuleApi\Registrar;

/**
 * Fixture that declares it stores user data (dataHandling) but ships no privacy
 * handler. It must block user:delete rather than letting it report success.
 */
class DataHandlingNoHandlerModule implements Module
{
    public function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: 'datahandling',
            name: 'Data Handling',
            version: '1.0.0',
            ccVersion: '*',
            dataHandling: ['Stores a row per user keyed by CID.'],
        );
    }

    public function register(Registrar $r): void {}
}
