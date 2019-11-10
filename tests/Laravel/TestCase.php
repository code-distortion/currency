<?php

namespace CodeDistortion\Currency\Tests\Laravel;

use CodeDistortion\Currency\Laravel\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Jchook\AssertThrows\AssertThrows;

/**
 * The test case that unit tests extend from
 */
class TestCase extends BaseTestCase
{
    use AssertThrows;

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app The Laravel app.
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class
        ];
    }
}
