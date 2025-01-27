<?php

namespace CodeDistortion\Currency\Tests;

use CodeDistortion\Currency\Laravel\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * The test case that unit tests extend from.
 */
class LaravelTestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app The Laravel app.
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class
        ];
    }
}
