<?php

namespace CodeDistortion\Currency\Tests\Laravel\Integration;

use App;
use CodeDistortion\Currency\Currency;
use CodeDistortion\Currency\Tests\Laravel\TestCase;

/**
 * Test the Currency's integration into Laravel
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class LaravelIntegrationTest extends TestCase
{
    /**
     * Test that the service-provider is registered in Laravel and acts correctly
     *
     * @todo get Laravel (orchestra-testbench) to pick up the service provider, and then perform the below test to
     *       check that the change-locale event is picked up.
     * @test
     * @return void
     */
    public function test_service_provider(): void
    {
        $this->assertSame('en', Currency::getDefaultLocale()); // default locale
        App::setLocale('en-AU');
        $this->assertSame('en-AU', Currency::getDefaultLocale());
    }
}
