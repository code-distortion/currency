<?php

namespace CodeDistortion\Currency\Tests\StandAlone\Unit;

use CodeDistortion\Currency\Currency;
use CodeDistortion\Currency\Exceptions\InvalidCurrencyException;
use CodeDistortion\Currency\Tests\StandAlone\TestCase;
use CodeDistortion\RealNum\Exceptions\InvalidValueException as RealNumInvalidValueException;
use CodeDistortion\RealNum\Exceptions\UndefinedPropertyException;
use CodeDistortion\RealNum\RealNum;
use PHPUnit\Framework\Constraint\Exception as ConstraintException;
use PHPUnit\Framework\Error\Warning;
use stdClass;

/**
 * Test the Currency library class
 *
 * @group standalone
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
class CurrencyUnitTest extends TestCase
{
    /**
     * Some alternate format settings used below for testing
     *
     * @var array
     */
    protected $altFormatSettings = [
        'null' => 'null',
        'decPl' => 5,
        'trailZeros' => false,
        'symbol' => false,
        'thousands' => false,
        'showPlus' => true,
        'accountingNeg' => true,
        'locale' => 'en-US',
        'breaking' => true,
    ];



    /**
     * Some set-up, run before each test
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // additional setup
        Currency::resetDefaults();
    }

    /**
     * Provides the different immutable situations to test for the test_currency_immutability_setters test below
     *
     * @return array
     */
    public function immutableDataProviderSetters(): array
    {
        $properties = [
            ['locale', 'locale', 'en-AU', 'en-NZ'],
            ['customDecPl', 'decPl', 14, 13],
            ['immutable', 'immutable', true, false],
            ['val', 'val', '1.00', '2.00'],
            ['cast', 'cast', 1, 2],
        ];

        $return = [];
        foreach ([true, false] as $immutable) {

            foreach ($properties as $values) {

                $setMethod = $values[0];
                $getField = $values[1];
                $startValue = $values[2];
                $endValue = $values[3];

                // swap the values when getting / setting the "immutable" value (and immutability is off)
                // (because it actually changes the immutable setting itself)
                if (($getField == 'immutable') && (!$immutable)) {
                    $startValue = $values[3];
                    $endValue = $values[2];
                }

                $return[] = [
                    $immutable,
                    $setMethod,
                    $getField,
                    $startValue,
                    $endValue,
                ];
            }
        }
        return $return;
    }

    /**
     * Provides the different render options for testing in the test_currency_locale_rendering test below
     *
     * @return array
     */
    public function localeRenderingDataProvider(): array
    {
        $output = [];
        $output['AUD']['en-AU'] = [
            '$12,345,678.90',
            '-$12,345,678.90',
            '$12,345,678.90',
            '($12,345,678.90)',
            '+$12,345,678.90',
            '12,345,678.90',
            '$12,345,678.00',
            '$12,345,678.90',
            '$12,345,678',
            '$12345678.00',
            '$0.00',
            'null',
            '$12345678',
            '(12345678)',
            '$12,345,678.90',
            '12,345,678.90',
        ];
        $output['AUD']['fr'] = [
            '12 345 678,90 $AU',
            '-12 345 678,90 $AU',
            '12 345 678,90 $AU',
            '(12 345 678,90 $AU)',
            '+12 345 678,90 $AU',
            '12 345 678,90',
            '12 345 678,00 $AU',
            '12 345 678,90 $AU',
            '12 345 678 $AU',
            '12345678,00 $AU',
            '0,00 $AU',
            'null',
            '12345678 $AU',
            '(12345678)',
            '12 345 678,90 $AU', // breaking spaces
            '12 345 678,90', // breaking spaces
        ];
        $output['AUD']['de'] = [
            '12.345.678,90 AU$',
            '-12.345.678,90 AU$',
            '12.345.678,90 AU$',
            '(12.345.678,90 AU$)',
            '+12.345.678,90 AU$',
            '12.345.678,90',
            '12.345.678,00 AU$',
            '12.345.678,90 AU$',
            '12.345.678 AU$',
            '12345678,00 AU$',
            '0,00 AU$',
            'null',
            '12345678 AU$',
            '(12345678)',
            '12.345.678,90 AU$', // breaking spaces
            '12.345.678,90',
        ];
        $output['AUD']['ja-JP'] = [
            'A$12,345,678.90',
            '-A$12,345,678.90',
            'A$12,345,678.90',
            '(A$12,345,678.90)',
            '+A$12,345,678.90',
            '12,345,678.90',
            'A$12,345,678.00',
            'A$12,345,678.90',
            'A$12,345,678',
            'A$12345678.00',
            'A$0.00',
            'null',
            'A$12345678',
            '(12345678)',
            'A$12,345,678.90',
            '12,345,678.90',
        ];
        $output['AUD']['en-IN'] = [
            'A$ 1,23,45,678.90',
            '-A$ 1,23,45,678.90',
            'A$ 1,23,45,678.90',
            '(A$ 1,23,45,678.90)',
            '+A$ 1,23,45,678.90',
            '1,23,45,678.90',
            'A$ 1,23,45,678.00',
            'A$ 1,23,45,678.90',
            'A$ 1,23,45,678',
            'A$ 12345678.00',
            'A$ 0.00',
            'null',
            'A$ 12345678',
            '(12345678)',
            'A$ 1,23,45,678.90', // breaking spaces
            '1,23,45,678.90',
        ];
        $output['AUD']['he'] = [
            '‏12,345,678.90 A$',
            '‏‎-12,345,678.90 A$',
            '‏12,345,678.90 A$',
            '(‏12,345,678.90 A$)',
            '‏‎+12,345,678.90 A$',
            '‏12,345,678.90',
            '‏12,345,678.00 A$',
            '‏12,345,678.90 A$',
            '‏12,345,678 A$',
            '‏12345678.00 A$',
            '‏0.00 A$',
            'null',
            '‏12345678 A$',
            '(‏12345678)',
            '‏12,345,678.90 A$', // breaking spaces
            '‏12,345,678.90',
        ];
        $output['AUD']['ar-EG'] = [
            '١٢٬٣٤٥٬٦٧٨٫٩٠ AU$',
            '؜-١٢٬٣٤٥٬٦٧٨٫٩٠ AU$',
            '١٢٬٣٤٥٬٦٧٨٫٩٠ AU$',
            '(١٢٬٣٤٥٬٦٧٨٫٩٠ AU$)',
            '؜+١٢٬٣٤٥٬٦٧٨٫٩٠ AU$',
            '١٢٬٣٤٥٬٦٧٨٫٩٠',
            '١٢٬٣٤٥٬٦٧٨٫٠٠ AU$',
            '١٢٬٣٤٥٬٦٧٨٫٩٠ AU$',
            '١٢٬٣٤٥٬٦٧٨ AU$',
            '١٢٣٤٥٦٧٨٫٠٠ AU$',
            '٠٫٠٠ AU$',
            'null',
            '١٢٣٤٥٦٧٨ AU$',
            '(١٢٣٤٥٦٧٨)',
            '١٢٬٣٤٥٬٦٧٨٫٩٠ AU$', // breaking spaces
            '١٢٬٣٤٥٬٦٧٨٫٩٠',
        ];
        $output['EUR']['en-AU'] = [
            'EUR 12,345,678.90',
            '-EUR 12,345,678.90',
            'EUR 12,345,678.90',
            '(EUR 12,345,678.90)',
            '+EUR 12,345,678.90',
            '12,345,678.90',
            'EUR 12,345,678.00',
            'EUR 12,345,678.90',
            'EUR 12,345,678',
            'EUR 12345678.00',
            'EUR 0.00',
            'null',
            'EUR 12345678',
            '(12345678)',
            'EUR 12,345,678.90', // breaking spaces
            '12,345,678.90',
        ];
        $output['EUR']['fr'] = [
            '12 345 678,90 €',
            '-12 345 678,90 €',
            '12 345 678,90 €',
            '(12 345 678,90 €)',
            '+12 345 678,90 €',
            '12 345 678,90',
            '12 345 678,00 €',
            '12 345 678,90 €',
            '12 345 678 €',
            '12345678,00 €',
            '0,00 €',
            'null',
            '12345678 €',
            '(12345678)',
            '12 345 678,90 €', // breaking spaces
            '12 345 678,90', // breaking spaces
        ];
        $output['EUR']['de'] = [
            '12.345.678,90 €',
            '-12.345.678,90 €',
            '12.345.678,90 €',
            '(12.345.678,90 €)',
            '+12.345.678,90 €',
            '12.345.678,90',
            '12.345.678,00 €',
            '12.345.678,90 €',
            '12.345.678 €',
            '12345678,00 €',
            '0,00 €',
            'null',
            '12345678 €',
            '(12345678)',
            '12.345.678,90 €', // breaking spaces
            '12.345.678,90',
        ];
        $output['INR']['en-IN'] = [
            '₹ 1,23,45,678.90',
            '-₹ 1,23,45,678.90',
            '₹ 1,23,45,678.90',
            '(₹ 1,23,45,678.90)',
            '+₹ 1,23,45,678.90',
            '1,23,45,678.90',
            '₹ 1,23,45,678.00',
            '₹ 1,23,45,678.90',
            '₹ 1,23,45,678',
            '₹ 12345678.00',
            '₹ 0.00',
            'null',
            '₹ 12345678',
            '(12345678)',
            '₹ 1,23,45,678.90', // breaking spaces
            '1,23,45,678.90',
        ];
        $output['ILS']['he'] = [
            '‏12,345,678.90 ₪',
            '‏‎-12,345,678.90 ₪',
            '‏12,345,678.90 ₪',
            '(‏12,345,678.90 ₪)',
            '‏‎+12,345,678.90 ₪',
            '‏12,345,678.90',
            '‏12,345,678.00 ₪',
            '‏12,345,678.90 ₪',
            '‏12,345,678 ₪',
            '‏12345678.00 ₪',
            '‏0.00 ₪',
            'null',
            '‏12345678 ₪',
            '(‏12345678)',
            '‏12,345,678.90 ₪', // breaking spaces
            '‏12,345,678.90',
        ];
        $output['EGP']['ar-EG'] = [
            '١٢٬٣٤٥٬٦٧٨٫٩٠ ج.م.‏',
            '؜-١٢٬٣٤٥٬٦٧٨٫٩٠ ج.م.‏',
            '١٢٬٣٤٥٬٦٧٨٫٩٠ ج.م.‏',
            '(١٢٬٣٤٥٬٦٧٨٫٩٠ ج.م.‏)',
            '؜+١٢٬٣٤٥٬٦٧٨٫٩٠ ج.م.‏',
            '١٢٬٣٤٥٬٦٧٨٫٩٠‏',
            '١٢٬٣٤٥٬٦٧٨٫٠٠ ج.م.‏',
            '١٢٬٣٤٥٬٦٧٨٫٩٠ ج.م.‏',
            '١٢٬٣٤٥٬٦٧٨ ج.م.‏',
            '١٢٣٤٥٦٧٨٫٠٠ ج.م.‏',
            '٠٫٠٠ ج.م.‏',
            'null',
            '١٢٣٤٥٦٧٨ ج.م.‏',
            '(١٢٣٤٥٦٧٨‏)',
            '١٢٬٣٤٥٬٦٧٨٫٩٠ ج.م.‏', // breaking spaces
            '١٢٬٣٤٥٬٦٧٨٫٩٠‏',
        ];



        $return = [];
        foreach (array_keys($output) as $curCode) {
            foreach ($output[$curCode] as $locale => $outputValues) {
                $return[] = [$locale, $curCode, 12345678.90, '', $outputValues[0]];
                $return[] = [$locale, $curCode, -12345678.90, '', $outputValues[1]];
                $return[] = [$locale, $curCode, 12345678.90, 'accountingNeg', $outputValues[2]];
                $return[] = [$locale, $curCode, -12345678.90, 'accountingNeg', $outputValues[3]];
                $return[] = [$locale, $curCode, 12345678.90, 'showPlus', $outputValues[4]];
                $return[] = [$locale, $curCode, 12345678.90, '!symbol', $outputValues[5]];
                $return[] = [$locale, $curCode, 12345678.00, '', $outputValues[6]];
                $return[] = [$locale, $curCode, 12345678.90, '!trailZeros', $outputValues[7]];
                $return[] = [$locale, $curCode, 12345678.00, '!trailZeros', $outputValues[8]];
                $return[] = [$locale, $curCode, 12345678.00, '!thousands', $outputValues[9]];
                $return[] = [$locale, $curCode, null, 'null=0', $outputValues[10]];
                $return[] = [$locale, $curCode, null, 'null="null"', $outputValues[11]];
                $return[] = [$locale, $curCode, 12345678.00, '!trailZeros !thousands', $outputValues[12]];
                $return[] = [
                    $locale,
                    $curCode,
                    -12345678.00,
                    '!symbol !trailZeros !thousands accountingNeg showPlus null="null"',
                    $outputValues[13]
                ];
                $return[] = [$locale, $curCode, 12345678.90, 'breaking', $outputValues[14]];
                $return[] = [$locale, $curCode, 12345678.90, 'breaking !symbol', $outputValues[15]];
            }
        }

        return $return;
    }







    /**
     * Test the ways the default locale, immutability and default-format settings are altered
     *
     * @test
     * @return void
     */
    public function test_currency_default_settings(): void
    {
        Currency::resetDefaults();
        $this->assertNull(Currency::getDefaultCurCode());
        Currency::setDefaultCurCode('USD');
        $this->assertSame('USD', Currency::getDefaultCurCode());
        Currency::setDefaultCurCode(null); // change back to null manually
        $this->assertNull(Currency::getDefaultCurCode());
        Currency::setDefaultCurCode('USD');
        Currency::resetDefaults();
        $this->assertNull(Currency::getDefaultCurCode());

        // make sure the Currency and RealNum settings are distinct
        Currency::resetDefaults();
        RealNum::resetDefaults();
        $this->assertSame('en', Currency::getDefaultLocale());
        $this->assertSame('en', RealNum::getDefaultLocale());
        $this->assertTrue(Currency::getDefaultImmutability());
        $this->assertTrue(RealNum::getDefaultImmutability());

        Currency::setDefaultLocale('en-AU');
        RealNum::setDefaultLocale('en-UK');
        $this->assertSame('en-AU', Currency::getDefaultLocale());
        $this->assertSame('en-UK', RealNum::getDefaultLocale());

        Currency::setDefaultImmutability(false);
        RealNum::setDefaultImmutability(true);
        $this->assertFalse(Currency::getDefaultImmutability());
        $this->assertTrue(RealNum::getDefaultImmutability());

        // check the default format-settings
        Currency::resetDefaults();
        $this->assertSame(Currency::ORIG_FORMAT_SETTINGS, Currency::getDefaultFormatSettings());
        $this->assertSame(Currency::ORIG_FORMAT_SETTINGS, Currency::new(null, 'USD')->formatSettings); // uses default
        Currency::setDefaultFormatSettings($this->altFormatSettings);
        $this->assertSame($this->altFormatSettings, Currency::getDefaultFormatSettings());
        $this->assertSame($this->altFormatSettings, Currency::new(null, 'USD')->formatSettings); // uses the new default
    }

    /**
     * Test the various ways of changing values in Currency when immutable / not immutable
     *
     * @test
     * @dataProvider immutableDataProviderSetters
     * @param boolean $immutable  Run the tests in immutable mode?.
     * @param string  $setMethod  The name of the method to call to set the value.
     * @param string  $getField   The name of the value to get to check the value afterwards.
     * @param mixed   $startValue The value to start with.
     * @param mixed   $endValue   The value to end up with.
     * @return void
     */
    public function test_currency_immutability_setters(
        bool $immutable,
        string $setMethod,
        string $getField,
        $startValue,
        $endValue
    ): void {

        $finalValue = ($immutable ? $startValue : $endValue); // either the value changed or it didn't

        // set the value directly (uses the __set magic method)
        // $currency = Currency::new('AUD')->immutable($immutable)->$setMethod($startValue);
        // $currency->$getField = $endValue; // not immutable when set this way
        // $this->assertSame($endValue, $currency->$getField);

        // set the value by calling the method
        $currency = Currency::new(null, 'AUD')->immutable($immutable)->$setMethod($startValue);
        $currency->$setMethod($endValue);
        $this->assertSame($finalValue, $currency->$getField);
    }

    /**
     * Test the ways the Currency class can be instantiated
     *
     * @test
     * @return void
     */
    public function test_currency_instantiation(): void
    {
        $this->assertNull(Currency::new(null, 'AUD')->cast);
        $this->assertNull((new Currency(null, 'AUD'))->cast);

        $this->assertSame(2, Currency::new(2, 'AUD')->cast);
        $this->assertSame(2, (new Currency(2, 'AUD'))->cast);

        $this->assertSame(2.24, Currency::new(2.239482390, 'AUD')->cast);
        $this->assertSame(2.24, (new Currency(2.239482390, 'AUD'))->cast);

        $this->assertSame(2.24, Currency::new('2.239482390', 'AUD')->cast);
        $this->assertSame(2.24, (new Currency('2.239482390', 'AUD'))->cast);

        $this->assertSame(2.24, Currency::new(Currency::new(2.239482390, 'AUD'), 'AUD')->cast);
        $this->assertSame(2.24, (new Currency(new Currency(2.239482390, 'AUD'), 'AUD'))->cast);

        $this->assertNull(Currency::new(null, 'AUD')->cast);
        $this->assertNull((new Currency(null, 'AUD'))->cast);

        // won't throw an exception for an invalid starting value
        $this->assertNull(Currency::new('abc', 'AUD', false)->cast);
        $this->assertNull((new Currency('abc', 'AUD', false))->cast);

        // cloning
        $cur = Currency::new(null, 'AUD')->immutable(true);
        $this->assertNotSame($cur, $cur->copy());
        $cur = Currency::new(null, 'AUD')->immutable(false);
        $this->assertNotSame($cur, $cur->copy());

        // picks up the default curCode
        Currency::resetDefaults();
        Currency::setDefaultCurCode('USD');
        $this->assertSame('$5.00', Currency::new(5)->format());
    }

    /**
     * Test setting various Currency values
     *
     * @test
     * @return void
     */
    public function test_setting_and_getting_currency_settings(): void
    {
        // LOCALE-resolver callback
        $callback = function () {
        };
        Currency::localeResolver($callback);
        $this->assertSame($callback, Currency::getLocaleResolver());
        Currency::localeResolver(null);
        $this->assertNull(Currency::getLocaleResolver());
        Currency::resetDefaults();

        // CURRENCY-resolver callback
        $callback = function () {
        };
        Currency::currencyResolver($callback);
        $this->assertSame($callback, Currency::getCurrencyResolver());
        Currency::currencyResolver(null);
        $this->assertNull(Currency::getCurrencyResolver());
        Currency::resetDefaults();



        // locale
        $this->assertSame('en', Currency::new(null, 'AUD')->locale); // uses the default
        $this->assertSame('en-AU', Currency::new(null, 'AUD')->locale('en-AU')->locale);

        // decPl
        $currency = Currency::new(null, 'AUD');
        $this->assertSame(2, Currency::new(null, 'AUD')->decPl); // uses the AUD default
        $this->assertNull(Currency::new(null, 'AUD')->customDecPl);
        $this->assertFalse(Currency::new(null, 'AUD')->usingCustomDecPl);
        $this->assertFalse(Currency::new(null, 'AUD')->usingCustomDecPl());

        $this->assertSame(0, Currency::new(null, 'JPY')->decPl); // uses the JPY default
        $this->assertSame(10, Currency::new(null, 'JPY')->customDecPl(10)->decPl);
        $this->assertSame(0, Currency::new(null, 'JPY')->decPl); // uses the JPY default

        // immutable
        $this->assertTrue(Currency::new(null, 'AUD')->immutable); // uses the default
        $this->assertFalse(Currency::new(null, 'AUD')->immutable(false)->immutable);



        // val (and default currency decPl)
        $currency = Currency::new(null, 'AUD')->val(10.101010);
        $this->assertSame('10.10', $currency->val);
        $currency = $currency->val(0);
        $this->assertSame('0.00', $currency->val);
        $currency = $currency->val(null);
        $this->assertNull($currency->val);

        // cast (and default currency decPl)
        $currency = Currency::new(null, 'AUD')->val(10.101010);
        $this->assertSame(10.10, $currency->cast);
        $currency = $currency->val(0);
        $this->assertSame(0, $currency->cast);
        $currency = $currency->val(null);
        $this->assertNull($currency->cast);

        // curCode (and getting the currency symbol)
        $currency = Currency::new(null, 'AUD');
        $this->assertSame('AUD', $currency->curCode);
        $this->assertSame('A$', $currency->symbol);
        $currency = $currency->curCode('USD');
        $this->assertSame('USD', $currency->curCode);
        $this->assertSame('$', $currency->symbol);
    }

    /**
     * Test the various methods that perform a Currency calculation
     *
     * @test
     * @return void
     */
    public function test_currency_altering(): void
    {
        $cur1 = Currency::new(5, 'AUD');
        $cur2 = Currency::new(2, 'AUD');
        $this->assertSame(7, $cur1->add($cur2)->cast);
    }

    /**
     * Test the different ways to the Currency value can be rendered
     *
     * @test
     * @dataProvider localeRenderingDataProvider
     * @param string            $locale        The locale to use.
     * @param string            $curCode       The currency to use.
     * @param float|null        $initialValue  The value to render.
     * @param string|array|null $renderOptions The options to use while rendering.
     * @param string|null       $expectedValue The expected render output.
     * @return void
     */
    public function test_currency_locale_rendering(
        string $locale,
        string $curCode,
        ?float $initialValue,
        $renderOptions,
        ?string $expectedValue
    ): void {

        $this->assertSame(
            $expectedValue,
            Currency::new($initialValue, $curCode)->locale($locale)->format($renderOptions)
        );
    }

    /**
     * Test the __toString magic method
     *
     * @test
     * @return void
     */
    public function test_currency_locale_casting_to_string(): void
    {
        $cur1 = Currency::new(1.234567890, 'AUD')->locale('en-AU');
        $this->assertSame('$1.23', (string) $cur1);

        $cur1 = Currency::new(1.2, 'AUD')->locale('en-AU');
        $this->assertSame('$1.20', (string) $cur1);

        $cur1 = Currency::new(1.2, 'NZD')->locale('en-AU');
        $this->assertSame('NZD 1.20', (string) $cur1);

        $cur1 = Currency::new(1.2, 'NZD')->locale('en-NZ');
        $this->assertSame('$1.20', (string) $cur1);

        $cur1 = Currency::new(1.2, 'JPY')->locale('en-NZ');
        $this->assertSame('JP¥1', (string) $cur1);
    }

    /**
     * Test how the Currency class handles different decimal places, and rounding
     *
     * @test
     * @return void
     */
    public function test_currency_decimal_places(): void
    {
        $newCur = function ($curCode) {
            return Currency::new(null, $curCode)->locale('en-AU');
        };

        $this->assertSame('1.2346', $newCur('AUD')->customDecPl(4)->val('1.234567890')->val);
        $this->assertSame('1.23', $newCur('AUD')->customDecPl(4)->val('1.234567890')->customDecPl(2)->val);
        $this->assertSame('1.2300', $newCur('AUD')->customDecPl(2)->val('1.234567890')->customDecPl(4)->val);
        $this->assertSame('1.23', $newCur('AUD')->customDecPl(4)->val('1.234567890')->useCurrencyDecPl()->val);
        $this->assertSame('1.234568', $newCur('AUD')->customDecPl(6)->val('1.234567890')->val);
        $this->assertSame('1.23', $newCur('AUD')->customDecPl(6)->val('1.234567890')->useCurrencyDecPl()->val);

        $this->assertSame(2, $newCur('AUD')->decPl);
        $this->assertSame(5, $newCur('AUD')->customDecPl(5)->decPl);

        // alter the customDecPl setting
        $currency = $newCur('AUD');
        $this->assertSame(2, $currency->decPl);
        $this->assertSame(null, $currency->customDecPl);
        $this->assertFalse($currency->usingCustomDecPl);
        $this->assertFalse($currency->usingCustomDecPl());
        $currency = $currency->customDecPl(10);
        $this->assertSame(10, $currency->decPl);
        $this->assertSame(10, $currency->customDecPl);
        $this->assertTrue($currency->usingCustomDecPl);
        $this->assertTrue($currency->usingCustomDecPl());
        $currency = $currency->useCurrencyDecPl();
        $this->assertSame(2, $currency->decPl);
        $this->assertSame(null, $currency->customDecPl);
        $this->assertFalse($currency->usingCustomDecPl);
        $this->assertFalse($currency->usingCustomDecPl());

        // detect different currency decimal places
        $this->assertSame(2, Currency::currencyDecPl('AUD'));
        $this->assertSame(0, Currency::currencyDecPl('JPY'));
        $this->assertSame(3, Currency::currencyDecPl('BHD'));

        // test rendering when there are the normal decimal places
        $currency = $newCur('AUD')->val(5.123456789);
        $this->assertSame('$5.12', $currency->format());

        // test rendering when there are more decimal places than normal
        $currency = $newCur('AUD')->customDecPl(20)->val(5.123456789);
        $this->assertSame('$5.123456789', $currency->format());
        $this->assertSame('$5.1235', $currency->format('decPl=4')); // when the decPl is explicitly specified

        // test rendering when decPl is specified explicitly
        $currency = $newCur('AUD')->customDecPl(20)->val(5.983456789);
        $this->assertSame('$5.983456789', $currency->format('decPl=null trailZeros'));
        $this->assertSame('$5.983456789', $currency->format('decPl=null')); // defaults to !trailZeros
        $this->assertSame('$5.983456789', $currency->format('decPl=null !trailZeros'));

        $this->assertSame('$5.983456789000000', $currency->format('decPl=15 trailZeros'));
        $this->assertSame('$5.983456789000000', $currency->format('decPl=15')); // defaults to trailZeros
        $this->assertSame('$5.983456789000000', $currency->format('decPl=15 !trailZeros'));

        $this->assertSame('$5.9835', $currency->format('decPl=4 trailZeros')); // rounded
        $this->assertSame('$5.9835', $currency->format('decPl=4 !trailZeros')); // rounded

        $this->assertSame('$6.0', $currency->format('decPl=1 trailZeros')); // rounded
        $this->assertSame('$6', $currency->format('decPl=1 !trailZeros')); // rounded

        $this->assertSame('$6', $currency->format('decPl=0 trailZeros')); // rounded
        $this->assertSame('$6', $currency->format('decPl=0 !trailZeros')); // rounded

        $currency = $newCur('AUD')->customDecPl(20)->val(5);
        $this->assertSame('$5.00', $currency->format('decPl=null trailZeros'));
        $this->assertSame('$5.00', $currency->format('decPl=null')); // defaults to !trailZeros
        $this->assertSame('$5', $currency->format('decPl=null !trailZeros'));

        $this->assertSame('$5.000000000000000', $currency->format('decPl=15 trailZeros'));
        $this->assertSame('$5.000000000000000', $currency->format('decPl=15')); // defaults to trailZeros
        $this->assertSame('$5', $currency->format('decPl=15 !trailZeros'));

        $this->assertSame('$5.0000', $currency->format('decPl=4 trailZeros')); // rounded
        $this->assertSame('$5', $currency->format('decPl=4 !trailZeros')); // rounded

        $this->assertSame('$5.0', $currency->format('decPl=1 trailZeros')); // rounded
        $this->assertSame('$5', $currency->format('decPl=1 !trailZeros')); // rounded

        $this->assertSame('$5', $currency->format('decPl=0 trailZeros')); // rounded
        $this->assertSame('$5', $currency->format('decPl=0 !trailZeros')); // rounded
    }

    /**
     * Test how the Currency class' default locale is set and used
     *
     * @test
     * @return void
     */
    public function test_currency_locale_changes(): void
    {

        // changes of locale
        Currency::setDefaultLocale('en-AU');
        $this->assertSame('en-AU', Currency::new(null, 'AUD')->locale);
        $this->assertSame('en-NZ', Currency::new(null, 'AUD')->locale('en-NZ')->locale);
        $this->assertSame('en-US', Currency::new(null, 'AUD')->locale('en-NZ')->locale('en-US')->locale);

        Currency::setDefaultLocale('en');
        $this->assertSame('A$', Currency::new(null, 'AUD')->symbol); // get the AUD symbol - when in en locale
        $this->assertSame('€', Currency::new(null, 'EUR')->symbol); // get the EUR symbol - when in en locale
        $this->assertSame('A$', Currency::symbol('AUD'));
        $this->assertSame('€', Currency::symbol('EUR'));

        Currency::setDefaultLocale('en-AU');
        $this->assertSame('$', Currency::new(null, 'AUD')->symbol); // get the AUD symbol - when in en-AU locale
        $this->assertSame('EUR', Currency::new(null, 'EUR')->symbol); // get the EUR symbol - when in en-AU locale
        $this->assertSame('$', Currency::symbol('AUD'));
        $this->assertSame('EUR', Currency::symbol('EUR'));

        Currency::setDefaultLocale('ja-JP');
        $this->assertSame('ja-JP', Currency::new(null, 'AUD')->locale);
        $this->assertSame('￥', Currency::new(null, 'JPY')->symbol); // JPY symbol - in jp-JP
        $this->assertSame('￥', Currency::symbol('JPY'));
        $this->assertSame('en-AU', Currency::new(null, 'AUD')->locale('en-AU')->locale);
        $this->assertSame('JPY', Currency::new(null, 'JPY')->locale('en-AU')->symbol); // JPY symbol - in en-AU

        Currency::resetDefaults();
        $this->assertSame('A$', Currency::symbol('AUD', 'en'));
        $this->assertSame('$', Currency::symbol('AUD', 'en-AU'));
        $this->assertSame('¥', Currency::symbol('JPY', 'en'));
        $this->assertSame('￥', Currency::symbol('JPY', 'ja-JP'));
    }

    /**
     * Test how the Currency class renders symbols in different currencies / locales
     *
     * @test
     * @return void
     */
    public function test_currency_symbols(): void
    {
        $this->assertSame('$', Currency::new(null, 'AUD')->locale('en-AU')->symbol);
        $this->assertSame('A$', Currency::new(null, 'AUD')->locale('en-US')->symbol);
        $this->assertSame('A$', Currency::new(null, 'AUD')->locale('en')->symbol);
        $this->assertSame('$', Currency::symbol('AUD', 'en-AU'));
        $this->assertSame('A$', Currency::symbol('AUD', 'en-US'));
        $this->assertSame('A$', Currency::symbol('AUD', 'en'));

        $this->assertSame('$', Currency::new(null, 'AUD')->locale('en-AU')->symbol);
        $this->assertSame('EUR', Currency::new(null, 'EUR')->locale('en-AU')->symbol);
        $this->assertSame('JPY', Currency::new(null, 'JPY')->locale('en-AU')->symbol);
        $this->assertSame('$', Currency::symbol('AUD', 'en-AU'));
        $this->assertSame('EUR', Currency::symbol('EUR', 'en-AU'));
        $this->assertSame('JPY', Currency::symbol('JPY', 'en-AU'));

        $this->assertSame('A$', Currency::new(null, 'AUD')->locale('en')->symbol);
        $this->assertSame('€', Currency::new(null, 'EUR')->locale('en')->symbol);
        $this->assertSame('¥', Currency::new(null, 'JPY')->locale('en')->symbol); // ...
        $this->assertSame('A$', Currency::symbol('AUD', 'en'));
        $this->assertSame('€', Currency::symbol('EUR', 'en'));
        $this->assertSame('¥', Currency::symbol('JPY', 'en'));

        $this->assertSame('A$', Currency::new(null, 'AUD')->locale('ja-JP')->symbol);
        $this->assertSame('€', Currency::new(null, 'EUR')->locale('ja-JP')->symbol);
        $this->assertSame('￥', Currency::new(null, 'JPY')->locale('ja-JP')->symbol); // a diff Yen symbol to above
                                                                                // because the locale is different
        $this->assertSame('A$', Currency::symbol('AUD', 'ja-JP'));
        $this->assertSame('€', Currency::symbol('EUR', 'ja-JP'));
        $this->assertSame('￥', Currency::symbol('JPY', 'ja-JP'));
    }

    /**
     * Test how to get the currency code from the Currency object
     *
     * @test
     * @return void
     */
    public function test_currency_codes(): void
    {
        $this->assertSame('AUD', Currency::new(null, 'AUD')->curCode);
        $this->assertSame('NZD', Currency::new(null, 'NZD')->curCode);
        $this->assertSame('JPY', Currency::new(null, 'AUD')->curCode('JPY')->curCode);

        $currency = Currency::new(null, 'AUD')->curCode('JPY');
        $this->assertSame('JPY', $currency->curCode);

        $this->assertSame(2, Currency::new(null, 'AUD')->decPl);
        $this->assertSame(0, Currency::new(null, 'AUD')->curCode('JPY')->decPl);
    }

    /**
     * Test the locale resolver, as a closure and as a class
     *
     * @test
     * @return void
     */
    public function test_currency_class_locale_resolver(): void
    {
        $closureWasRun = false;
        $localeResolver = function ($localeIdentifier) use (&$closureWasRun) {
            $closureWasRun = true;
            return ($localeIdentifier === 99 ? 'en-AU' : null);
        };

        Currency::localeResolver($localeResolver);
        $this->assertSame('en-AU', Currency::new(null, 'AUD')->locale(99)->locale);
        $this->assertTrue($closureWasRun);
    }

    /**
     * Test the currency resolver
     *
     * @test
     * @return void
     */
    public function test_currency_class_currency_resolver(): void
    {

        $closureWasRun = false;
        $currencyResolver = function ($currencyIdentifier) use (&$closureWasRun) {
            $closureWasRun = true;
            return ($currencyIdentifier === 36 ? 'AUD' : null);
        };

        Currency::currencyResolver($currencyResolver);
        $this->assertSame('AUD', Currency::new(null, 'AUD')->curCode(36)->curCode);
        $this->assertTrue($closureWasRun);
    }

    /**
     * Test the different values that Currency can use
     *
     * @test
     * @return void
     */
    public function test_currency_accepted_value_types(): void
    {
        $this->assertSame(5, Currency::new(5, 'AUD')->cast);
        $this->assertSame(5, Currency::new('5', 'AUD')->cast);
        $this->assertSame(5.1, Currency::new(5.1, 'AUD')->cast);

        $cur2 = Currency::new(5, 'AUD');
        $this->assertSame(5, Currency::new($cur2, 'AUD')->cast);

        // PHPUnit\Framework\Constraint\Exception is required by jchook/phpunit-assert-throws
        if (class_exists(ConstraintException::class)) {

            // initial value is invalid - boolean
            $this->assertThrows(RealNumInvalidValueException::class, function () {
                Currency::new(true, 'AUD'); // phpstan false positive
            });

            // initial value is invalid - non-numeric string
            $this->assertThrows(RealNumInvalidValueException::class, function () {
                Currency::new('abc', 'AUD');
            });

            // initial value is invalid - object
            $this->assertThrows(RealNumInvalidValueException::class, function () {
                Currency::new(new stdClass(), 'AUD'); // phpstan false positive
            });
        }
    }

    /**
     * Test the ways Currency generates exceptions
     *
     * @test
     * @return void
     */
    public function test_currency_exceptions(): void
    {

        // PHPUnit\Framework\Constraint\Exception is required by jchook/phpunit-assert-throws
        if (class_exists(ConstraintException::class)) {

            // (pseudo-)property abc doesn't exist to get
            $this->assertThrows(UndefinedPropertyException::class, function () {
                Currency::new(null, 'AUD')->abc; // phpstan false positive
            });

            // (pseudo-)property abc doesn't exist to SET
            // $this->assertThrows(UndefinedPropertyException::class, function () {
            //     $currency = Currency::new('AUD');
            //     $currency->abc = true; // phpstan false positive
            // });

            // no currency given
            $this->assertThrows(InvalidCurrencyException::class, function () {
                $currency = Currency::new(); // phpstan false positive
            });

            // invalid value to add
            $this->assertThrows(RealNumInvalidValueException::class, function () {
                Currency::new(null, 'AUD')->add(true); // phpstan false positive
            });

            // division by 0
            $this->assertThrows(Warning::class, function () {
                Currency::new(1, 'AUD')->div(0);
            });

            // currency mismatch
            $this->assertThrows(InvalidCurrencyException::class, function () {
                $cur2 = Currency::new(2.239482390, 'NZD');
                Currency::new($cur2, 'AUD'); // invalid starting value
            });

            // currency mismatch
            $this->assertThrows(
                InvalidCurrencyException::class,
                function () {
                    $cur1 = Currency::new(5, 'AUD');
                    $cur2 = Currency::new(2, 'NZD');
                   $cur1->add($cur2);
                }
            );

            // currency mismatch
            $this->assertThrows(
                InvalidCurrencyException::class,
                function () {
                    $cur1 = Currency::new(5, 'AUD');
                    $cur2 = Currency::new(2, 'NZD');
                    $cur1->lt($cur2);
                }
            );

            // unresolvable currency
            $this->assertThrows(InvalidCurrencyException::class, function () {
                Currency::new(null, 1);
            });

            // unresolvable currency
            $this->assertThrows(InvalidCurrencyException::class, function () {
                Currency::new(null, 'AUD')->curCode(1);
            });
        }
    }
}
