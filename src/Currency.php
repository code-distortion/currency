<?php

namespace CodeDistortion\Currency;

use CodeDistortion\Currency\Exceptions\InvalidCurrencyException;
use CodeDistortion\Options\Options;
use CodeDistortion\RealNum\Base;
use CodeDistortion\RealNum\Exceptions\InvalidLocaleException;
use CodeDistortion\RealNum\Exceptions\InvalidValueException;
use CodeDistortion\RealNum\Exceptions\UndefinedPropertyException;
use NumberFormatter;
use Throwable;

/**
 * Represents currency values, performs calculations & comparisons on them, and renders them in different locales.
 *
 * @property callable|null $currencyResolver
 * @property string        $curCode
 * @property string        $symbol
 * @property int           $decPl
 * @property int|null      $customDecPl
 * @property boolean       $usingCustomDecPl
 */
class Currency extends Base
{
    /** @var integer|string|null The original default curCode - used when resetting the class-level defaults. */
    public const ORIG_DEFAULT_CUR_CODE = null;

    /** @var array The original default format-settings - used when resetting the class-level defaults. */
    public const ORIG_FORMAT_SETTINGS = [
        'null' => null,
        'decPl' => null,
        'trailZeros' => true,
        'symbol' => true,
        'thousands' => true,
        'showPlus' => false,
        'accountingNeg' => false,
        'locale' => 'en',
        'breaking' => false,
    ];



    /**
     * The default currency code to use (at the class-level).
     *
     * Objects will pick this value up when instantiated (if not specified when instantiating).
     *
     * @var integer|string|null
     */
    protected static $defaultCurCode = null;

    /**
     * The default maximum number of decimal places available to use (at the class-level).
     *
     * Objects will pick this value up when instantiated.
     *
     * @var integer
     */
    protected static $defaultMaxDecPl = 20;

    /**
     * The default immutable-setting (at the class-level).
     *
     * Objects will pick this value up when instantiated.
     *
     * @var boolean
     */
    protected static $defaultImmutable = true;

    /**
     * The default settings to use when formatting the number (at the class-level).
     *
     * Objects will pick this value up when instantiated.
     *
     * @var array
     */
    protected static $defaultFormatSettings = [
        'null' => null,
        'decPl' => null,
        'trailZeros' => true,
        'symbol' => true,
        'thousands' => true,
        'showPlus' => false,
        'accountingNeg' => false,
        'locale' => 'en',
        'breaking' => false,
    ];





    /**
     * Callback used to resolve localeIdentifiers.
     *
     * It may for example understand database ids, and map them back to their 'en-AU' equivalent.
     * When this hasn't been set, the locales are assumed to be strings like 'en-AU' and treated as is.
     *
     * @var callable|null
     */
    protected static $localeResolver = null;

    /**
     * Callback used to resolve currencyIdentifiers.
     *
     * It may for example understand database ids, and map them back to their 'AUD' equivalent.
     * When this hasn't been set, the currency-codes are assumed to be strings like 'AUD' and treated as is.
     *
     * @var callable|null
     */
    protected static $currencyResolver = null;





    /**
     * The currency this object is currently using.
     *
     * @var string|null
     */
    protected $curCode = null;

    /**
     * The custom number of decimal places to use instead of the current currency's regular amount.
     *
     * @var integer|null
     */
    protected $customDecPl = null;





    /**
     * An internal cache of the number of decimal places per currency.
     *
     * @var array
     */
    protected static $currencyDecPl = [];

    /**
     * An internal of the currency symbols, in each locale.
     *
     * @var array
     */
    protected static $currencySymbols = [];


    /**
     * Constructor.
     *
     * @param integer|float|string|self|null $value          The initial value to store.
     * @param integer|string|null            $curCode        The currency the $value is in.
     * @param boolean                        $throwException Should an exception be thrown if the $value is invalid?
     *                                                       (the value will be set to null upon error otherwise).
     * @throws InvalidCurrencyException Thrown when a curCode wasn't passed and no default hasn't been specified.
     * @throws InvalidValueException    Thrown when the given value is invalid (and $throwException is true).
     */
    public function __construct($value = null, $curCode = null, bool $throwException = true)
    {
        // fall-back to the default curCode if needed
        if (is_null($curCode)) {
            if (is_null(static::$defaultCurCode)) {

                throw InvalidCurrencyException::currencyNotSpecified();
            }
            $curCode = static::$defaultCurCode;
        }

        $this->setCurCode($curCode);
        parent::__construct($value, $throwException);
    }

    /**
     * Build a new Currency object.
     *
     * @param integer|float|string|self|null $value          The initial value to store.
     * @param integer|string|null            $curCode        The currency the $value is in.
     * @param boolean                        $throwException Should an exception be thrown if the $value is invalid?
     *                                                       (the value will be set to null upon error otherwise).
     * @return static
     * @throws InvalidCurrencyException Thrown when a curCode wasn't passed and no default hasn't been specified.
     * @throws InvalidValueException    Thrown when the given value is invalid (and $throwException is true).
     */
    public static function new($value = null, $curCode = null, bool $throwException = true)
    {
        return new static($value, $curCode, $throwException);
    }







    /**
     * Set the default values back to their original value.
     *
     * This is used during unit tests as these default values are static properties.
     *
     * @return void
     */
    public static function resetDefaults(): void
    {
        parent::resetDefaults();
        static::$defaultCurCode = static::ORIG_DEFAULT_CUR_CODE;
        static::$currencyResolver = null;
        static::$currencyDecPl = [];
        static::$currencySymbols = [];
    }

    /**
     * Retrieve the default curCode.
     *
     * @return integer|string|null
     */
    public static function getDefaultCurCode()
    {
        return static::$defaultCurCode;
    }

    /**
     * Update the default curCode.
     *
     * @param integer|string|null $curCode The curCode to set.
     * @return void
     */
    public static function setDefaultCurCode($curCode): void
    {
        static::$defaultCurCode = $curCode;
    }


    /**
     * Get various values stored in this object.
     *
     * @param string $name The field to get.
     * @return mixed
     * @throws InvalidCurrencyException   Thrown when the currency cannot be resolved.
     * @throws UndefinedPropertyException Thrown when accessing an invalid field.
     * @throws InvalidLocaleException     Thrown when the locale cannot be resolved.
     * @internal
     */
    public function __get(string $name)
    {
        switch ($name) {

            // return the currencyResolver
            case 'currencyResolver':
                return static::$currencyResolver;



            // return the effective curCode
            case 'curCode':
                return $this->getCurCode();

            // return the current curCode's symbol (eg. '$')
            case 'symbol':
                return static::renderSymbol($this->effectiveCurCode(), $this->effectiveLocale());

            // return the maximum number of decimal places available to use
            case 'decPl':
                return $this->maxDecPl;

            // return the "custom" number of decimal places currently set
            case 'customDecPl':
                return ($this->usingCustomDecPl() ? $this->maxDecPl : null);

            // return whether a custom number of decimal places is set
            case 'usingCustomDecPl':
                return $this->usingCustomDecPl();



            // see if the parent can handle this
            default:
                return parent::__get($name);
        }
    }







    /**
     * Update the currencyResolver.
     *
     * @param callable|null $currencyResolver A closure used resolve currency-identifiers.
     * @return void
     */
    public static function currencyResolver(?callable $currencyResolver): void
    {
        static::$currencyResolver = $currencyResolver;
    }

    /**
     * Return the currencyResolver.
     *
     * @return callable|null
     */
    public static function getCurrencyResolver(): ?callable
    {
        return static::$currencyResolver;
    }

    /**
     * Take the given $currencyIdentifier and return the relevant currency-code.
     *
     * @param integer|string|null $currencyIdentifier The currency to resolve.
     * @return string
     * @throws InvalidCurrencyException Thrown when the currency cannot be resolved.
     */
    protected static function resolveCurrencyCode($currencyIdentifier): string
    {
        if (!is_null($currencyIdentifier)) {

            // via callback
            if (is_callable(static::$currencyResolver)) {
                $currency = (static::$currencyResolver)($currencyIdentifier);
                if ((is_string($currency)) && (mb_strlen($currency))) {
                    return $currency;
                }
            }
        }

        if ((is_string($currencyIdentifier)) && (mb_strlen($currencyIdentifier))) {
            return $currencyIdentifier;
        }

        throw InvalidCurrencyException::unresolveableCurrency($currencyIdentifier);
    }


    /**
     * Set the curCode this object uses.
     *
     * @param integer|string $curCode The currency code to set.
     * @return static
     * @throws InvalidCurrencyException Thrown when the currency cannot be resolved.
     */
    public function curCode($curCode): self
    {
        return $this->immute()->setCurCode($curCode); // chainable - immutable
    }

    /**
     * Let the caller set a custom number of decimal places to use.
     *
     * @param integer $decPl The custom number of decimal places to use.
     * @return static
     */
    public function customDecPl(int $decPl): self
    {
        $currency = $this->immute()->setDecPl($decPl);
        $currency->customDecPl = $decPl;
        return $currency; // chainable - immutable
    }

    /**
     * Let the caller know if customDecPl is currently being used.
     *
     * @return boolean
     */
    public function usingCustomDecPl(): bool
    {
        return (!is_null($this->customDecPl));
    }

    /**
     * Set the decimal-places to match the current currency.
     *
     * @return static
     */
    public function useCurrencyDecPl(): self
    {
        $decPl = static::determineCurrencyDecPl((string) $this->curCode);
        $currency = $this->immute()->setDecPl($decPl);
        $currency->customDecPl = null;
        return $currency; // chainable - immutable
    }

    /**
     * Retrieve the number of decimal places for the given (or current) currency.
     *
     * @param integer|string $curCode The currency to use.
     * @return integer|null
     * @throws InvalidCurrencyException Thrown when the currency cannot be resolved.
     */
    public static function currencyDecPl($curCode): ?int
    {
        return static::determineCurrencyDecPl(
            static::resolveCurrencyCode($curCode)
        );
    }

    /**
     * Determine the currency symbol of the given currency in the given locale.
     *
     * @param integer|string      $curCode The currency to get the symbol for.
     * @param integer|string|null $locale  The locale to use when getting the symbol.
     * @return string
     * @throws InvalidCurrencyException Thrown when the currency cannot be resolved.
     * @throws InvalidLocaleException   Thrown when the locale cannot be resolved.
     */
    public static function symbol($curCode, $locale = null): string
    {
        return static::renderSymbol(
            static::resolveCurrencyCode($curCode),
            static::resolveLocaleCode(mb_strlen((string) $locale) ? $locale : static::getDefaultLocale())
        );
    }

    /**
     * Format the current value in a readable way.
     *
     * @param string|array|null $options The options to use when rendering the number.
     * @return string
     * @throws InvalidCurrencyException Thrown when the currency cannot be resolved.
     * @throws InvalidLocaleException   Thrown when the locale cannot be resolved.
     */
    public function format($options = null): ?string
    {
        $value = $this->getVal();
        $parsedOptions = Options::new($options)->all();
        $resolvedOptions = Options::new($parsedOptions)->defaults($this->formatSettings)->all();

        // customise what happens when the value is null
        if ((!is_string($value)) || (!mb_strlen($value))) {
            try {
                $value = static::extractBasicValue(
                    $resolvedOptions['null'],
                    $this->internalMaxDecPl(),
                    false // don't pick up a 'null' string as null
                );
            } catch (Throwable $e) {
                return $resolvedOptions['null']; // it could be a string like 'null'
            }
        }

        // render the value if it's a number
        $curCode = static::resolveCurrencyCode($this->curCode);
        if (($curCode) && (is_string($value)) && (mb_strlen($value))) {

            $locale = $this->resolveLocaleCode($resolvedOptions['locale']); // locale can be specified by the caller
            $curCode = strtoupper($curCode);
            $showSymbol = (bool) $resolvedOptions['symbol'];
            $decPl = $resolvedOptions['decPl'];
            $maxDecPl = $this->internalMaxDecPl();



            // if decPl was specified then force trailZeros to be on
            if (!is_null($decPl)) {
                // (as long as the caller didn't explicitly pass a trailZeros setting in the first place)
                if (!array_key_exists('trailZeros', $parsedOptions)) {
                    $resolvedOptions['trailZeros'] = true;
                }
            // otherwise use the normal number of decimal places, and leave trailZeros alone
            } else {

                // start with the currency's normal number of decimal places
                $decPl = static::determineCurrencyDecPl($curCode);

                // use all the decimal values if desired (there may be more digits than the currency normally has)
                // if ($showAllDecimalDigits) {
                    // have at least the number the currency has - but possibly more
                    $decPl = max($decPl, static::howManyDecimalPlaces($value));
                // }
            }

            // remove trailing zeros if desired and there is no decimal value
            if (!$resolvedOptions['trailZeros']) {
                if (
                    static::roundCalculation($value, 0, $maxDecPl)         // check if the whole number
                    === static::roundCalculation($value, $decPl, $maxDecPl)
                ) { // equals the num to the correct decPl

                    // @todo this isn't respected when showing currency values in currencies that don't belong to
                    // the locale (or something like this) and may need to be compensated for (ie. php code to
                    // correct). this seems to be an ICU 57.1 bug (see https://bugs.php.net/bug.php?id=63140 )
                    $decPl = 0;
                }
            }



            $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decPl);
            // remove the thousands separator if desired
            if (!$resolvedOptions['thousands']) {
                $numberFormatter->setAttribute(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, 0);
            }



            // render the number
            $callback = function ($value) use ($numberFormatter, $curCode, $locale, $showSymbol) {
                $number = $numberFormatter->formatCurrency($value, $curCode);

                // remove the currency symbol if desired
                if (!$showSymbol) {
                    $symbol = static::renderSymbol($curCode, $locale);

                    // remove LEFT-TO-RIGHT MARK, RIGHT-TO-LEFT MARK from the symbol
                    $symbol = str_replace(["\xe2\x80\x8e", "\xe2\x80\x8f"], ' ', $symbol);

                    // remove the currency symbol, and any whitespace around it
                    // (including NON-BREAKING SPACE and NARROW NO-BREAK SPACE)
                    $number = trim(
                        (string) preg_replace(
                            "/( |\xc2\xa0|\xe2\x80\xaf)*" . preg_quote($symbol, '/') . "( |\xc2\xa0|\xe2\x80\xaf)*/",
                            '',
                            $number
                        )
                    );
                }
                return $number;
            };

            $return = $this->renderNumber(
                $value,
                $maxDecPl,
                $locale,
                (bool) $resolvedOptions['accountingNeg'],
                (bool) $resolvedOptions['showPlus'],
                (bool) $resolvedOptions['breaking'],
                $numberFormatter,
                $callback
            );

            // compensate for the extra nbsp space being added to the currency character
            // this happens when en-IN is used - in some environments
            if ($locale == 'en-IN') {
                $symbol = static::renderSymbol($curCode, $locale);
                $return = str_replace([$symbol . ' ', $symbol . "\xc2\xa0"], $symbol, $return);
            }

            return $return;
        }

        return null;
    }







    /**
     * Determine the number of decimal places in the given currency - STATIC.
     *
     * @param string $curCode The currency to get the decimal places for.
     * @return integer
     */
    protected static function determineCurrencyDecPl(string $curCode): int
    {
        if (!array_key_exists($curCode, static::$currencyDecPl)) {

            // generate 1 unit of the given currency in a known locale, and see how many decimal places it has
            // en-US - a locale whose output format is known (eg. $1.00, ¥1, .. BHD1.000)
            $numberFormatter = new NumberFormatter('en-US', NumberFormatter::CURRENCY);
            $oneUnit = $numberFormatter->formatCurrency(1, strtoupper($curCode));
            static::$currencyDecPl[$curCode] = (
                (preg_match('/1\.([0]*)$/', $oneUnit, $matches))
                ? mb_strlen($matches[1])
                : 0
            );
        }
        return static::$currencyDecPl[$curCode];
    }

    /**
     * Determine the currency symbol of the given currency in the given locale - STATIC.
     *
     * @param string $curCode The currency to get the decimal places for.
     * @param string $locale  The locale to use when getting the symbol.
     * @return string
     */
    protected static function renderSymbol(string $curCode, string $locale): string
    {
        // use the cached symbol if it has already been worked out
        if (isset(static::$currencySymbols[$locale][$curCode])) {
            $symbol = static::$currencySymbols[$locale][$curCode];
        } else {

            // it would be nice to use this, but this doesn't allow for a currency to be specified
            // it uses the currency default for the locale
            // $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            // $symbol = trim($numberFormatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL));

            // render two numbers in the given currency/locale and work out where the differences start and stop
            $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $numberA = $numberFormatter->formatCurrency(1.1111, strtoupper($curCode));
            $numberB = $numberFormatter->formatCurrency(6.6666, strtoupper($curCode));

            // remove NON-BREAKING SPACE, NARROW NO-BREAK SPACE, LEFT-TO-RIGHT MARK, RIGHT-TO-LEFT MARK
            // are any others needed here?
            $numberA = str_replace(["\xc2\xa0", "\xe2\x80\xaf", "\xe2\x80\x8e", "\xe2\x80\x8f"], ' ', $numberA);
            $numberB = str_replace(["\xc2\xa0", "\xe2\x80\xaf", "\xe2\x80\x8e", "\xe2\x80\x8f"], ' ', $numberB);

            // which characters are different between these two strings
            $differentCharPos = [];
            for ($count = 0; $count < mb_strlen($numberA); $count++) {
                if (mb_substr($numberA, $count, 1) != mb_substr($numberB, $count, 1)) {
                    $differentCharPos[] = $count;
                }
            }

            // assume the symbol is at the beginning or the end
            $beginDiff = mb_substr($numberA, 0, min($differentCharPos));
            $endDiff = mb_substr($numberA, max($differentCharPos) + 1);

            // use the longest one (usually one of these is empty, it's not in hebrew 'he' though for example)
            $symbol = (mb_strlen($beginDiff) >= mb_strlen($endDiff) ? $beginDiff : $endDiff);
            $symbol = trim($symbol);

            static::$currencySymbols[$locale][$curCode] = $symbol;
        }
        return $symbol;
    }







    /**
     * Returns an immutable version of this object (if enabled).
     *
     * @param boolean $force Will always immute when true.
     * @return static
     */
    protected function immute(bool $force = false): Base
    {
        return (($force) || ($this->effectiveImmutable()) ? clone $this : $this);
    }

    /**
     * Retrieve the currencyCode.
     *
     * @return string|null
     */
    protected function getCurCode(): ?string
    {
        return $this->curCode;
    }

    /**
     * Store the given value.
     *
     * @param mixed $curCode The currency to use.
     * @return static
     * @throws InvalidCurrencyException Thrown when the currency cannot be resolved.
     */
    protected function setCurCode($curCode): self
    {
        $this->curCode = static::resolveCurrencyCode($curCode);
        if (!$this->usingCustomDecPl()) {
            // don't use ->currencyDecPl because that is immutable and won't change this object
            $decPl = static::determineCurrencyDecPl($this->curCode);
            $this->setDecPl($decPl);
        }
        return $this; // chainable - NOT immutable
    }

    /**
     * Check if the passed value is compatible for operations on this object.
     *
     * (This may be overridden by child classes).
     *
     * @param mixed   $value          The value to check against the value stored in this object.
     * @param boolean $throwException Should an exception be raised if the given value isn't valid?.
     * @return boolean
     * @throws InvalidCurrencyException Thrown when the given value is invalid (and $throwException is true).
     * @throws InvalidValueException    Thrown when the given value is invalid (and $throwException is true).
     */
    protected function ensureCompatibleValue($value, bool $throwException = true): bool
    {
        // this object is compatible with other objects of the same type
        $exception = null;
        if ((is_object($value)) && ($value instanceof static)) {

            // the object must have the same curCode
            if ($value->curCode != $this->curCode) {
                $exception = InvalidCurrencyException::incompatibleCurrencies($value->curCode, $this->curCode);
            }
        }

        // check if an error was found
        if (!is_null($exception)) {

            // throw an exception if necessary
            if ($throwException) {
                throw $exception;
            }
            return false;
        }

        // check for other types of value
        return parent::ensureCompatibleValue($value, $throwException);
    }


    /**
     * Use the given currency, but use the current one if needed.
     *
     * @param integer|string|null $currencyIdentifier The currency to force (otherwise the current one is used).
     * @return string
     * @throws InvalidCurrencyException Thrown when the currency cannot be resolved.
     */
    protected function effectiveCurCode($currencyIdentifier = null): string
    {
        if (!is_null($currencyIdentifier)) {
            $curCode = static::resolveCurrencyCode($currencyIdentifier);
            if (mb_strlen($curCode)) {
                return $curCode;
            }
        }
        return (string) $this->curCode;
    }
}
