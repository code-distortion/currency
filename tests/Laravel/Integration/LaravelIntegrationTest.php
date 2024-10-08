<?php

namespace CodeDistortion\Currency\Tests\Laravel\Integration;

use App;
use CodeDistortion\Currency\Currency;
use CodeDistortion\Currency\Tests\Laravel\TestCase;

/**
 * Test the Currency's integration into Laravel.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class LaravelIntegrationTest extends TestCase
{
    /**
     * Test that the service-provider is registered in Laravel and acts correctly.
     *
     * @test
     * @return void
     */
    public function test_service_provider(): void
    {
        self::assertSame('en', Currency::getDefaultLocale()); // default locale
        App::setLocale('en-AU');
        self::assertSame('en-AU', Currency::getDefaultLocale());
    }
}
