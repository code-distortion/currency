# Currency

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/currency.svg?style=flat-square)](https://packagist.org/packages/code-distortion/currency) ![PHP from Packagist](https://img.shields.io/packagist/php-v/code-distortion/currency?style=flat-square) ![Laravel](https://img.shields.io/badge/laravel-5%20%26%206-blue?style=flat-square) [![contributor covenant](https://img.shields.io/badge/contributor%20covenant-v1.4%20adopted-ff69b4.svg?style=flat-square)](code-of-conduct.md)

***code-distortion/currency*** is a PHP library for accurate currency maths with locale-aware formatting. It integrates with Laravel 5 & 6 but works stand-alone as well.

It uses PHP's [BCMath](https://www.php.net/manual/en/book.bc.php) extension to avoid inaccurate floating point calculations:

``` php
// an example of floating-point inaccuracy
var_dump(0.1 + 0.2 == 0.3); // bool(false)
// for more details see The Floating-Point Guide - https://floating-point-gui.de/
```

Amounts are formatted in different locales using PHP's [NumberFormatter](https://www.php.net/manual/en/class.numberformatter.php). Some examples include:

| Currency | en-US | de-DE | sv-SE | hi-IN | ar-EG |
| :----: | :----: | :----: | :----: | :----: | :----: |
| USD | $1,234,567.89 | 1.234.567,89 $ | 1 234 567,89 US$ | $12,34,567.89 | ١٬٢٣٤٬٥٦٧٫٨٩ US$ |
| EUR | €1,234,567.89 | 1.234.567,89 € | 1 234 567,89 € | €12,34,567.89 | ١٬٢٣٤٬٥٦٧٫٨٩ € |
| SEK | SEK 1,234,567.89 | 1.234.567,89 SEK | 1 234 567,89 kr | SEK 12,34,567.89 | ١٬٢٣٤٬٥٦٧٫٨٩ SEK |
| JPY | ¥1,234,568 | 1.234.568 ¥ | 1 234 568 JPY | JP¥12,34,568 | ١٬٢٣٤٬٥٦٨ JP¥ |
| INR | ₹1,234,567.89 | 1.234.567,89 ₹ | 1 234 567,89 INR | ₹12,34,567.89 | ١٬٢٣٤٬٥٦٧٫٨٩ ₹ |
| EGP | EGP 1,234,567.89 | 1.234.567,89 EGP | 1 234 567,89 EG£ | EGP 12,34,567.89 | ١٬٢٣٤٬٥٦٧٫٨٩ ج.م.‏ |

If you would like to work with regular *floating-point* or *percentage* values, please consider the [code-distortion/realnum](https://github.com/code-distortion/realnum) package.

## Installation

You can install the package via composer:

```bash
composer require code-distortion/currency
```

## Usage

Instantiate a Currency object and you can start performing calculations with it, perform comparisons, and render it as a readable string:
``` php
use CodeDistortion\Currency\Currency;

$cur1 = new Currency('USD', 5555.55); // normal instantiation
$cur1 = Currency::new('USD', 5555.55); // static instantiation which is more readable when chaining

$cur2 = $cur1->add(4444.44); // (it's immutable so a new object is created)
$cur2->between(8000, 10000); // true
print $cur2->format(); // "$9,999.99"
```

### Setting values

You may set the value explicitly:
``` php
$cur1 = Currency::new('USD', 5); // the amount is set to $5.00 straight away
$cur2 = $cur1->val(10); // and is then set to $10.00 (it's immutable so a new object is created)
```

The types of values you can pass to Currency are:

``` php
$cur1 = Currency::new('USD', 5); // an integer
$cur2 = Currency::new('USD', 5.5); // a float
$cur3 = Currency::new('USD', '6'); // a numeric string
$cur4 = Currency::new('USD', $cur3); // another Currency object
$cur5 = Currency::new('USD', null); // null
$cur6 = Currency::new('USD'); // (will default to null)
```

***TIP:*** To maintain precision when setting values, pass them as strings instead of floating-point numbers:

``` php
$cur = Currency::new('USD')->customDecPl(20)->val(0.12345678901234567890); // "0.12345678901235" (precision lost - the number was passed as a float)
$cur = Currency::new('USD')->customDecPl(20)->val('0.12345678901234567890'); // "0.12345678901234567890" (passed as a string)
```

You may also set other settings that Currency uses:

``` php
$cur = Currency::new('USD');
$cur = $cur->locale('en-US'); // sets the locale this object uses (see the 'locale' section below)
$cur = $cur->curCode('USD'); // change the currency used
$cur = $cur->customDecPl(30); // sets the number of decimal places used (see the 'precision (custom decimal places)' section below)
$cur = $cur->useCurrencyDecPl(); // uses the current currency's decimal places again
$cur = $cur->immutable(false); // sets whether this object is immutable or not (see the 'immutability' section below)
$cur = $cur->noBreakWhitespace(true); // sets whether this object will use non-breaking whitespace when format() is called or not (see the 'non-breaking whitespace' section below)
```

### Retrieving values

To retrieve the value contained in a Currency object you may read the `val` and `cast` properties. The `val` property maintains precision and in contrast, `cast` will loose some precision so use them depending on your needs:

``` php
$cur = Currency::new('USD', '0.12345678901234567890');
print $cur->val; // "0.12345678901234567890" (returned as a string, or null)
print $cur->cast; // 0.12345678901235 (cast to either an integer, float or null - this is less accurate)
```

These properties associated to the currency may be read:

```php
$cur = Currency::new('USD', 1);
print $cur->curCode; // USD (the current currency code)
print $cur->symbol; // $ (the currency symbol in the current locale)
print $cur->decPl; // 2 (the number of decimal places in the current currency)
```

You may also read other settings that Currency uses:

``` php
$cur = Currency::new('USD');
print $cur->customDecPl; // null (see the 'precision (custom decimal places)' section below)
print $cur->usingCustomDecPl; // false (see the 'precision (custom decimal places)' section below)
print $cur->locale; // "en"
print $cur->immutable; // true
print $cur->noBreakWhitespace; // false
```

And you can also obtain each currency's symbol:

```php
print Currency::symbol('USD'); // "$" (will pick-up the current default locale 'en')
print Currency::symbol('USD', 'en-US'); // "$" (for a specific locale)
print Currency::symbol('USD', 'en-IN'); // "US$" (same currency, but a different symbol)
print Currency::symbol('USD', 'en-AU'); // "USD"
print Currency::symbol('JPY', 'en-US'); // "¥"
print Currency::symbol('JPY', 'ja-JP'); // "￥"
```

***Note:*** See the [formatting output](#formatting-output) section below for more details about how to render the value as a readable string.

### Calculations

The calculations you may perform are:

``` php
$cur = Currency::new('USD', 5);
$cur = $cur->inc(); // increment
$cur = $cur->dec(); // decrement
$cur = $cur->add(2); // add x
$cur = $cur->sub(2); // subtract x
$cur = $cur->div(2); // divide by x
$cur = $cur->mul(2); // multiply by x
$cur = $cur->round(); // round to zero decimal places
$cur = $cur->round(2); // round to x decimal places
$cur = $cur->floor(); // use the floor of the current value
$cur = $cur->ceil(); // use the ceiling of the current value
```

You may pass multiple values to `add()`, `sub()`, `div()` and `mul()`:

```php
Currency::new('USD', 5)->add(4, 3, 2, 1); // $15.00
Currency::new('USD', 5)->sub(4, 3, 2, 1); // -$5.00
Currency::new('USD', 5)->customDecPl(15)->div(4, 3, 2, 1); // $0.208333333333333
Currency::new('USD', 5)->mul(4, 3, 2, 1); // $120.00
```

You may pass: *integer*, *float*, *numeric string* and *null* values, as well as other *Currency* objects.

```php
$cur1 = Currency::new('USD', 5);
$cur1 = $cur1->add(2); // pass an integer
$cur1 = $cur1->add(2.0); // pass a float
$cur1 = $cur1->add('2'); // pass a numeric string
$cur1 = $cur1->add(null); // pass null (adds nothing)
$cur2 = Currency::new('USD', 2);
$cur1 = $cur1->add($cur2); // pass another Currency
```

### Comparisons

You can compare amounts to others with bound checking:

``` php
Currency::new('USD', 5)->lessThan(10); // alias of lt(..)
Currency::new('USD', 5)->lessThanOrEqualTo(10); // alias of lte(..)
Currency::new('USD', 5)->equalTo(10); // alias of eq(..)
Currency::new('USD', 5)->greaterThanOrEqualTo(10); // alias of gte(..)
Currency::new('USD', 5)->greaterThan(10); // alias of gt(..)

$cur1 = Currency::new('USD', 5);
$cur2 = Currency::new('USD', 6);
$cur1->lt($cur2); // you can compare a Currency with others
```

You may pass multiple values to these comparison methods. eg.

``` php
Currency::new('USD', 5)->lt(10, 15, 20); // will return true if 5 is less-than 10, 15 and 20
```

You can check if a Currency's value is between given bounds:

``` php
Currency::new('USD', 5)->between(2, 8); // check if 5 is between x and y (inclusively)
Currency::new('USD', 5)->between(2, 8, false); // check if 5 is between x and y (NOT inclusively)
```

### Formatting output

Use the `format()` method to generate a readable-string version of the current value:

``` php
$cur = Currency::new('USD', 1234567.89);
print $cur->format(); // "$1,234,567.89"
```

You may alter the way `format()` renders the output by passing options:

``` php
print Currency::new('USD', 1234567.89)->format(Currency::NO_THOUSANDS); // "$1234567.89" (removes the thousands separator)
print Currency::new('USD', 1234567.89)->format(Currency::SHOW_PLUS); // "+$1,234,567.89" (adds a '+', only for positive values)
print Currency::new('USD', -1234567.89)->format(Currency::ACCT_NEG); // "($1,234,567.89)" (uses brackets for negative numbers)
print Currency::new('USD', 1234567.89)->format(Currency::NO_SYMBOL); // "1,234,567.89" (removes the currency symbol)

print Currency::new('USD', null)->format(); // null (will return actual null by default)
print Currency::new('USD', null)->format(Currency::NULL_AS_ZERO); // "$0.00"
print Currency::new('USD', null)->format(Currency::NULL_AS_STRING); // "null"

print Currency::new('USD')->symbol; // "$"

print Currency::new('USD', 1)->format(); // "$1.00" (includes the decimal places by default)
print Currency::new('USD', 1)->format(Currency::NO_ZEROS); // "$1" (hides the decimal places when zero)

// non-breaking spaces can be returned instead of spaces - see the 'non-breaking whitespace' section below for more details
print htmlentities(
    Currency::new('USD', 1234567.89)->locale('sv-SE')->format(Currency::NO_BREAK_WHITESPACE)
); // "1&nbsp;234&nbsp;567,89&nbsp;US$" (when using Swedish)
```

You may use several settings at the same time:

```php
print Currency::new('USD', 1234567.89)->format(Currency::NO_THOUSANDS | Currency::SHOW_PLUS); // "+$1234567.89"
```

You may also choose the number of decimal places to show at the time of rendering:

``` php
print Currency::new('USD', 1234567.89)->format(null, 5); // "$1,234,567.89000" (5 decimal places)
```

The `format()` method output will generate the correct output for the current locale:

``` php
print Currency::new('USD', 1234567.89)->locale('en')->format(); // "$1,234,567.89" (English)
print Currency::new('USD', 1234567.89)->locale('en-AU')->format(); // "USD 1,234,567.89" (Australian English)
print Currency::new('USD', 1234567.89)->locale('en-IN')->format(); // "US$ 12,34,567.89" (Indian English)
print Currency::new('USD', 1234567.89)->locale('de')->format(); // "1.234.567,89 $" (German)
print Currency::new('USD', 1234567.89)->locale('sv')->format(); // "1 234 567,89 US$" (Swedish)
print Currency::new('USD', 1234567.89)->locale('ar')->format(); // "١٬٢٣٤٬٥٦٧٫٨٩ US$" (Arabic)
```

Casting a Currency to a string is equivalent to calling `format()` with no arguments:

```php
print (string) Currency::new('USD', 1234567.89); // "$1,234,567.89"
```

***NOTE***: Currency uses PHP's NumberFormatter to render the readable output, which currently has a limitation of being able to only show about 17 digits (including before the decimal place). So `format()`'s output will act a bit strangely if there are too many digits. The number stored inside will maintain it's full accuracy however. You may access the full number by reading the `val` property (see the [retrieving values](#retrieving-values) section above).

### Locale

***Note:*** When using Laravel this will be set automatically. See the [Laravel](#laravel) section below.

Currency's default locale is "en" (English) but you can choose which one to use.

You may change the locale per-object:

``` php
$cur1 = Currency::new('USD', 1234567.89);
print $cur1->locale; // "en" (the default)
print $cur1->format(); // "$1,234,567.89"
$cur2 = $cur1->locale('fr-FR'); // (it's immutable so a new object is created)
print $cur2->locale; // "fr-FR"
print $cur2->format(); // "1 234 567,89 $US"
```

The locale may be changed by default. All ***new*** Currency objects will start with this setting:

``` php
Currency::setDefaultLocale('fr-FR');
print Currency::getDefaultLocale(); // "fr-FR"
```

### Precision (custom decimal places)

The number of decimal places the current currency has is used by default (eg. 2 decimal places for USD). But you may specify the number to use for greater precision. This may be useful if you wish to perform some calculations and then round to the nearest cent at the end.

You may change this per-object using `customDecPl()`:

``` php
// without customDecPl
$cur = Currency::new('USD', '0.98765'); // this has more decimal places than USD has
print $cur->val; // "0.99" (ie. rounded to the default 2 decimal places)
print $cur->decPl; // 2
print $cur->customDecPl; // null
print $cur->usingCustomDecPl; // false

// with customDecPl
$cur = Currency::new('USD')->customDecPl(30)->val('0.123456789012345678901234567890');
print $cur->val; // "0.123456789012345678901234567890" (the full 30 decimal places)
print $cur->decPl; // 30
print $cur->customDecPl; // 30
print $cur->usingCustomDecPl; // true
```

You can revert back to the normal number of decimal places using `currencyDecPl()`:

```php
$cur = $cur->useCurrencyDecPl(); // goes back to USD's 2 decimal places - the amount inside will be rounded automatically
print $cur->decPl; // 2
```

To find out how many decimal places a currency has:

```php
print Currency::currencyDecPl('USD'); // 2
print Currency::currencyDecPl('JPY'); // 0
```

### Immutability

***Note:*** When using Laravel you may set this in the package config file. See the [Laravel](#laravel) section below.

Currency is immutable by default which means that once an object is created it won't change. Anything that changes the value will return a new Currency instead. You can then pass a Currency object to other parts of your code and be sure that it won't be changed unexpectedly:

``` php
$cur1 = Currency::new('USD', 1);
$cur2 = $cur1->add(2); // $cur1 remains unchanged and $cur2 is a new object containing the new value
print $cur1->format(); // "$1.00"
print $cur2->format(); // "$3.00"
```

Immutability may be turned off per-object:

``` php
$cur1 = Currency::new('USD', 1)->immutable(false);
$cur2 = $cur1->add(2); // $cur1 is changed and $cur2 points to the same object
print $cur1->format(); // "$3.00"
print $cur2->format(); // "$3.00"
```

Immutability may be turned off by default. All ***new*** Currency objects will start with this setting:

``` php
Currency::setDefaultImmutability(false);
var_dump(Currency::getDefaultImmutability()); // "bool(false)"
```

You can explicitly make a clone of a Currency object:

```php
$cur1 = Currency::new('USD');
$cur2 = $cur1->copy(); // this will return a clone regardless of the immutability setting
```

### Non-breaking whitespace

***Note:*** When using Laravel you may set this in the package config file. See the [Laravel](#laravel) section below.

Some locales use spaces when rendering numbers (eg. Swedish use spaces for the thousands separator). `format()` can either return strings containing regular space characters, or with non-breaking space characters instead.

An example of non-breaking whitespace is UTF-8's `\xc2\xa0` character which is used instead of a regular `\x20` space character. There are others like `\xe2\x80\xaf` which is a 'narrow no-break space'.

The `\xc2\xa0` UTF-8 character will become the familiar `&nbsp;` when turned into an html-entity.

By default Currency uses regular spaces, but you instruct it to return non-breaking whitespace when calling `format()`:

``` php
$cur = Currency::new('USD', 1234567.89)->locale('sv-SE'); // Swedish
print htmlentities($cur->format()); // "1 234 567,89 US$" (regular spaces)
print htmlentities($cur->format(Currency::NO_BREAK_WHITESPACE)); // "1&nbsp;234&nbsp;567,89&nbsp;US$" (contains non-breaking whitespace)
```

Non-breaking whitespace may be turned on per-object:

``` php
$cur1 = Currency::new('USD', 1234567.89)->locale('sv-SE'); // Swedish
print htmlentities($cur1->format()); // "1 234 567,89 US$ (regular spaces)
$cur2 = $cur1->noBreakWhitespace(true); // (it's immutable so a new object is created)
print htmlentities($cur2->format()); // "1&nbsp;234&nbsp;567,89&nbsp;US$" (contains non-breaking whitespace)
```

Non-breaking whitespace may be turned on by default. All ***new*** Currency objects will start with this setting:

``` php
Currency::setDefaultNoBreakWhitespace(true);
var_dump(Currency::getDefaultNoBreakWhitespace()); // "bool(true)"
```

### Chaining
The *setting* and *calculation* methods above may be chained together:

``` php
print Currency::new('USD', 1)
->locale('en-US')->val(5)->customDecPl(3) // some "setting" methods
->add(4)->mul(3)->div(2)->sub(1) // some "calculation" methods
->format(); // "$12.50"
```

### Laravel

The Currency package is framework agnostic and works well on it's own, but it also integrates with Laravel 5 & 6.

#### Service-provider

Currency integrates with Laravel 5.5+ automatically thanks to Laravel's package auto-detection. For Laravel 5.0 - 5.4, add the following line to **config/app.php**:

``` php
'providers' => [
  ...
CodeDistortion\Currency\Laravel\ServiceProvider::class,
  ...
],
```

The service-provider will register the starting locale with Currency and update it if it changes, so you don't have to.

#### Config

You may specify default immutability and non-breaking-whitespace values by publishing the **config/currency.php** config file and updating it:

``` bash
php artisan vendor:publish --provider="CodeDistortion\Currency\Laravel\ServiceProvider" --tag="config"
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Code of conduct

Please see [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

### Security

If you discover any security related issues, please email tim@code-distortion.net instead of using the issue tracker.

## Credits

- [Tim Chandler](https://github.com/code-distortion)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com).
