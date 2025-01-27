<?php

namespace CodeDistortion\Currency\Tests\Laravel\Integration;

use App;
use CodeDistortion\Currency\Currency;
use CodeDistortion\Currency\Tests\LaravelTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test the Currency's integration into Laravel.
 *
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class LaravelIntegrationLaravelTest extends LaravelTestCase
{
    /**
     * Test that the service-provider is registered in Laravel and acts correctly.
     *
     * @test
     *
     * @return void
     */
    #[Test]
    public function test_service_provider(): void
    {
        self::assertSame('en', Currency::getDefaultLocale()); // default locale
        App::setLocale('en-AU');
        self::assertSame('en-AU', Currency::getDefaultLocale());
    }
}
