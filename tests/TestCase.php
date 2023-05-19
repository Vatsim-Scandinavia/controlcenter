<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
    * Configures the default list of transactioned connections
    **/
    protected function connectionsToTransact()
    {
        return [
            config('database.default')
        ];
    }
}
