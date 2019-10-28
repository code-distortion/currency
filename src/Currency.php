<?php

namespace CodeDistortion\Currency;

use CodeDistortion\RealNum\Base;
use NumberFormatter;
use InvalidArgumentException;

/**
 * Manage currency values with accuracy, renderable in different locales
 *
 * Represents currency values, performs calculations & comparisons on them, and renders them.
 * @property ?callable $currencyResolver
 * @property string $curCode
 * @property string $symbol
 * @property int $decPl
 * @property ?int $customDecPl
 * @property boolean $usingCustomDecPl
 */
class Currency extends Base
{
    /**
     * The default locale (at the class-level)
     *
     * Objects will pick this value up when instantiated.
     * @var integer|string
     */
    protected static $defaultLocale = 'en';

    /**
     * The default maximum number of decimal places available to use (at the class-level)
     *
     * Objects will pick this value up when instantiated.
     * @var integer
     */
    protected static $defaultMaxDecPl = 20;

    /**
     * The default immutable-setting (at the class-level).
     *
     * Objects will pick this value up when instantiated.
     * @var boolean
     */
    protected static $defaultImmutable = true;

    /**
     * The default non-breaking-whitespace setting (at the class-level).
     *
     * Used when formatting a number.
     * Objects will pick this value up when instantiated.
     * @var boolean
     */
    protected static $defaultNoBreakWhitespace = false;





    /**
     * Callback used to resolve localeIdentifiers
     *
     * It may for example understand database ids, and map them back to their 'en-AU' equivalent.
     * When this hasn't been set, the locales are assumed to be strings like 'en-AU' and treated as is.
     * @var ?callable
     */
    protected static $localeResolver = null;

    /**
     * Callback used to resolve currencyIdentifiers
     *
     * It may for example understand database ids, and map them back to their 'AUD' equivalent.
     * When this hasn't been set, the currency-codes are assumed to be strings like 'AUD' and treated as is.
     * @var ?callable
     */
    protected static $currencyResolver = null;





    /**
     * The currency this object is currently using
     *
     * @var ?string
     */
    protected $curCode = null;

    /**
     * The custom number of decimal places to use instead of the current currency's regular amount
     *
     * @var ?integer
     */
    protected $customDecPl = null;





    /**
     * An internal cache of the number of decimal places per currency
     *
     * @var array
     */
    protected static $currencyDecPl = [];

    /**
     * An internal of the currency symbols, in each locale
     *
     * @var array
     */
    protected static $currencySymbols = [];





    /**
     * Affects formatting - don't show the decimal places when there is no decimal place value
     */
    const NO_ZEROS = 1;

    /**
     * Affects formatting - show the trailing zeros at the end of the decimal numbers
     */
    // const SHOW_DECIMAL_ZEROS = 2;

    /**
     * Affects formatting - removes the thousands separator
     */
    const NO_THOUSANDS = 4;

    /**
     * Affects formatting - the plus is normally omitted (unlike a negative minus),
     * show the plus sign for positive values
     */
    const SHOW_PLUS = 8;

    /**
     * Affects formatting - show positive and negative values in accounting format
     * (ie. show negative numbers in brackets)
     */
    const ACCT_NEG = 16;

    /**
     * Affects formatting - will return 0 instead of null
     */
    const NULL_AS_ZERO = 32;

    /**
     * Affects formatting - should (the string) "null" be rendered for null values (otherwise actual null is returned)
     */
    const NULL_AS_STRING = 64;

    /**
     * Affects formatting - normally non-breaking spaces and other characters are returned as regular spaces. using this
     * will leave them as they were
     *
     */
    const NO_BREAK_WHITESPACE = 128;

    /**
     * Affects formatting - don't use the currency symbol
     */
    const NO_SYMBOL = 256;







    /**
     * Constructor
     *
     * @param integer|string                 $curCode        The currency the $value is in.
     * @param integer|float|string|self|null $value          The initial value to store.
     * @param boolean                        $throwException Should an exception be thrown if the $value is invalid?
     *                                                       (the value will be set to null upon error otherwise).
     */
    public function __construct($curCode, $value = null, bool $throwException = true)
    {
        $this->setCurCode($curCode);
        parent::__construct($value, $throwException);
    }

    /**
     * Build a new Currency object
     *
     * @param integer|string                 $curCode        The currency the $value is in.
     * @param integer|float|string|self|null $value          The initial value to store.
     * @param boolean                        $throwException Should an exception be thrown if the $value is invalid?
     *                                                       (the value will be set to null upon error otherwise).
     * @return static
     */
    public static function new($curCode, $value = null, bool $throwException = true)
    {
        return new static($curCode, $value, $throwException);
    }







    /**
     * Set the default values back to their original value
     *
     * This is used during unit tests as these default values are static properties
     * @return void
     */
    public static function resetDefaults(): void
    {
        parent::resetDefaults();
        static::$currencyResolver = null;
        static::$currencyDecPl = [];
        static::$currencySymbols = [];
    }







    /**
     * Get various values stored in this object
     *
     * @internal
     * @param string $name The field to get.
     * @return mixed
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
        }

        // see if the parent can handle this
        return parent::__get($name);
    }

    /**
     * Set various values stored in this object
     *
     * @param string $name  The name of the value to set.
     * @param mixed  $value The value to store.
     * @return void
     */
    // public function __set(string $name, $value)
    // {
    //     this object may be immutable so don't allow it to be updated like this
    //     switch ($name) {

    //         // set the currencyResolver
    //         case 'currencyResolver':
    //             static::$currencyResolver = $value;
    //             return;

    //         // set the locale
    //         case 'curCode':
    //             $this->setCurCode($value);
    //             return;
    //     }

    //     // see if the parent class can handle this
    //     parent::__set($name, $value);
    // }







    /**
     * Update the currencyResolver
     *
     * @param callable|null $currencyResolver A closure used resolve currency-identifiers.
     * @return void
     */
    public static function currencyResolver(?callable $currencyResolver): void
    {
        static::$currencyResolver = $currencyResolver;
    }

    /**
     * Return the currencyResolver
     *
     * @return ?callable
     */
    public static function getCurrencyResolver(): ?callable
    {
        return static::$currencyResolver;
    }

    /**
     * Take the given $currencyIdentifier and return the relevant currency-code
     *
     * @param integer|string|null $currencyIdentifier The currency to resolve.
     * @return string
     * @throws InvalidArgumentException Thrown when the $currencyIdentifier can not be resolved.
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

        throw new InvalidArgumentException('Currency code "'.$currencyIdentifier.'" could not be resolved');
    }







    /**
     * Set the curCode this object uses
     *
     * @param integer|string $curCode The currency code to set.
     * @return static
     */
    public function curCode($curCode): self
    {
        return $this->immute()->setCurCode($curCode); // chainable - immutable
    }

    /**
     * Let the caller set a custom number of decimal places to use
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
     * Let the caller know if customDecPl is currently being used
     *
     * @return boolean
     */
    public function usingCustomDecPl(): bool
    {
        return (!is_null($this->customDecPl));
    }

    /**
     * Set the decimal-places to match the current currency
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
     * Retrieve the number of decimal places for the given (or current) currency
     *
     * @param integer|string $curCode The currency to use.
     * @return integer|null
     */
    public static function currencyDecPl($curCode): ?int
    {
        return static::determineCurrencyDecPl(
            static::resolveCurrencyCode($curCode)
        );
    }

    /**
     * Determine the currency symbol of the given currency in the given locale
     *
     * @param integer|string      $curCode The currency to get the symbol for.
     * @param integer|string|null $locale  The locale to use when getting the symbol.
     * @return string
     */
    public static function symbol($curCode, $locale = null): string
    {
        return static::renderSymbol(
            static::resolveCurrencyCode($curCode),
            static::resolveLocaleCode(mb_strlen((string) $locale) ? $locale : static::$defaultLocale)
        );
    }

    /**
     * Format the current number in a readable way
     *
     * @param integer|null $options The render options made up from Currency constants (eg. Currency::NO_THOUSANDS).
     * @param integer|null $decPl   The number of decimal places to render to.
     * @return string
     */
    public function format(?int $options = 0, int $decPl = null): ?string
    {
        $value = $this->getVal();
        $options = (int) $options;
        $curCode = static::resolveCurrencyCode($this->curCode);

        // render nulls as 0 if desired
        if (((!is_string($value)) || (!mb_strlen($value)))
        && ((bool) ($options & static::NULL_AS_ZERO))) {
            $value = '0';
        }

        if (($curCode) && (is_string($value)) && (mb_strlen($value))) {

            $removeDecimalsWhenZero = (bool) ($options & static::NO_ZEROS);
            // $showAllDecimalDigits   = true; // shows more decimal digits (if present) than the currency normally has
            $noThousands            = (bool) ($options & static::NO_THOUSANDS);
            $showPlus               = (bool) ($options & static::SHOW_PLUS);
            $accountingNegative     = (bool) ($options & static::ACCT_NEG);
            $noSymbol               = (bool) ($options & static::NO_SYMBOL);
            // otherwise fall back to the current non-breaking-whitespace setting
            $noBreakWhitespace      = (($options & static::NO_BREAK_WHITESPACE)
                                        ? true
                                        : $this->effectiveNoBreakWhitespace());

            $locale   = $this->effectiveLocale();
            $curCode  = strtoupper($curCode);
            $maxDecPl = $this->internalMaxDecPl();



            // if no decPl was explicitly specified then...
            if (is_null($decPl)) {

                // start with the currency's normal number of decimal places
                $decPl = static::determineCurrencyDecPl($curCode);

                // use all the decimal values if desired (there may be more digits than the currency normally has)
                // if ($showAllDecimalDigits) {
                    // have at least the number the currency has - but possibly more
                    $decPl = max($decPl, static::howManyDecimalPlaces($value));
                // }

                // if desired by the caller and the number has no decimal value, remove the decimal digits
                if ($removeDecimalsWhenZero) {
                    if (static::roundCalculation($value, 0, $maxDecPl)         // check if the whole number
                    === static::roundCalculation($value, $decPl, $maxDecPl)) { // equals the num to the correct decPl

                        // @todo this isn't respected when showing currency values in currencies that don't belong to
                        // the locale (or something like this) and may need to be compensated for (ie. php code to
                        // correct). this seems to be an ICU 57.1 bug (see https://bugs.php.net/bug.php?id=63140 )
                        $decPl = 0;
                    }
                }
            }



            $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
            $numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decPl);
            // remove the thousands separator if desired
            if ($noThousands) {
                $numberFormatter->setAttribute(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, null);
            }



            // render the number
            $callback = function ($value) use ($numberFormatter, $curCode, $locale, $noSymbol) {
                $number = $numberFormatter->formatCurrency($value, $curCode);

                // remove the currency symbol if desired
                if ($noSymbol) {
                    $symbol = static::renderSymbol($curCode, $locale);

                    // remove LEFT-TO-RIGHT MARK, RIGHT-TO-LEFT MARK from the symbol
                    $symbol = str_replace(["\xe2\x80\x8e", "\xe2\x80\x8f"], ' ', $symbol);

                    // remove the currency symbol, and any whitespace around it
                    // (including NON-BREAKING SPACE and NARROW NO-BREAK SPACE)
                    $number = trim(
                        (string) preg_replace(
                            "/( |\xc2\xa0|\xe2\x80\xaf)*".preg_quote($symbol, '/')."( |\xc2\xa0|\xe2\x80\xaf)*/",
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
                $accountingNegative,
                $showPlus,
                $noBreakWhitespace,
                $numberFormatter,
                $callback
            );

            return $return;
        }

        $showNull = (bool) ($options & static::NULL_AS_STRING);
        return ($showNull ? 'null' : null);
    }







    /**
     * Determine the number of decimal places in the given currency - STATIC
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
     * Determine the currency symbol of the given currency in the given locale - STATIC
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

            // which characters are different beween these two strings
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
     * Returns an immutable version of this object (if enabled)
     *
     * @param boolean $force Will allways immute when true.
     * @return static
     */
    protected function immute(bool $force = false): Base
    {
        return (($force) || ($this->effectiveImmutable()) ? clone $this : $this);
    }

    /**
     * Retrieve the currencyCode
     *
     * @return string|null
     */
    protected function getCurCode(): ?string
    {
        return $this->curCode;
    }

    /**
     * Store the given value
     *
     * @param mixed $curCode The currency to use.
     * @return static
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
     * Check if the passed value is compatible for operations on this object
     *
     * (This may be overridden by child classes)
     * @param mixed   $value          The value to check against the value stored in this object.
     * @param boolean $throwException Should an exception be raised if the given value isn't valid?.
     * @return boolean
     * @throws InvalidArgumentException Thrown when the given value is invalid (and $throwException is true).
     */
    protected function ensureCompatibleValue($value, bool $throwException = true): bool
    {
        // this object is compatible with other objects of the same type
        $exceptionMsg = null;
        if ((is_object($value)) && ($value instanceof static)) {

            // the object must have the same curCode
            if ($value->curCode != $this->curCode) {
                $exceptionMsg = 'Currency code mismatch - '.$value->curCode.' is not compatible with '.$this->curCode;
            }
        }

        // check if an error was found
        if (is_string($exceptionMsg)) {

            // throw an exception if necessary
            if ($throwException) {
                throw new InvalidArgumentException($exceptionMsg);
            }
            return false;
        }

        // check for other types of value
        return parent::ensureCompatibleValue($value, $throwException);
    }



    /**
     * Use the given currency, but use the current one if needed
     *
     * @param integer|string|null $currencyIdentifier The currency to force (otherwise the current one is used).
     * @return string
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
