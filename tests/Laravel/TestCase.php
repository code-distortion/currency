<?php

namespace CodeDistortion\Currency\Tests\Laravel;

use Orchestra\Testbench\TestCase as BaseTestCase;

use Jchook\AssertThrows\AssertThrows;
use CodeDistortion\Currency\Laravel\ServiceProvider;

/**
 * The test case that unit tests extend from
 */
class TestCase extends BaseTestCase
{
    use AssertThrows;

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
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
