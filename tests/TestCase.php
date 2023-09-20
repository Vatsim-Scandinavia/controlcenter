<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        /**
         * This mocks Vite while running tests, removing the need to either run a development mode or to build assets.
         */
        $this->withoutVite();
    }

    /**
     * Configures the default list of transactioned connections
     **/
    protected function connectionsToTransact()
    {
        return [
            config('database.default'),
        ];
    }
}
