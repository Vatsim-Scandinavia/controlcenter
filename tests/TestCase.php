<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
    * Configures the default list of transactioned connections to include
    * the mandatory, tightly coupled Handover connection.
    *
    * @todo Remove along with tightly coupled Handover database connection.
    **/
    protected function connectionsToTransact()
    {
        return [
            config('database.default'),
            config('database.handover')
        ];
    }
}
