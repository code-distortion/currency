# Currency

[![Latest Version on Packagist](https://img.shields.io/packagist/v/code-distortion/currency.svg?style=flat-square)](https://packagist.org/packages/code-distortion/currency)
![PHP from Packagist](https://img.shields.io/packagist/php-v/code-distortion/currency?style=flat-square)
![Laravel](https://img.shields.io/badge/laravel-5%20%26%206-blue?style=flat-square)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/code-distortion/currency/run-tests?label=tests&style=flat-square)](https://github.com/code-distortion/currency/actions)
[![Buy us a tree](https://img.shields.io/badge/treeware-%F0%9F%8C%B3-lightgreen?style=flat-square)](https://offset.earth/treeware?gift-trees)
[![Contributor Covenant](https://img.shields.io/badge/contributor%20covenant-v2.0%20adopted-ff69b4.svg?style=flat-square)](CODE_OF_CONDUCT.md)


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

Install the package via composer:

```bash
composer require code-distortion/currency
```

## Usage

Instantiate a Currency object and you can start performing calculations with it, perform comparisons, and render it as a readable string:
``` php
use CodeDistortion\Currency\Currency;

$cur1 = new Currency(5555.55, 'USD');  // normal instantiation
$cur1 = Currency::new(5555.55, 'USD'); // static instantiation which is more readable when chaining

// you may specify a default currency-code
Currency::setDefaultCurCode('USD');
$cur1 = new Currency(5555.55);  // normal instantiation
$cur1 = Currency::new(5555.55); // static instantiation which is more readable when chaining

$cur2 = $cur1->add(4444.44); // (it's immutable so a new object is created)
$cur2->between(8000, 10000); // true
print $cur2->format();       // "$9,999.99"
```

### Default currency-code

The default currency-code isn't set to begin with but you can choose one. If you use Laravel this may be set in the /config/currency.php config file. See the [Laravel](#laravel) section below for more details. Otherwise you may:

``` php
Currency::new(5, 'USD'); // ok - $5 USD
Currency::new(5);        // exception "Currency-code was not specified. Please pass one or specify a default"

var_dump(Currency::getDefaultCurCode()); // null
Currency::setDefaultCurCode('JPY');
print Currency::getDefaultCurCode();     // 'JPY'

Currency::new(5, 'USD'); // ok - $5 USD
Currency::new(5);        // ok - $5 JPY
```

***Note:*** All examples below use a default currency-code of USD.

### Setting values

You may set the value explicitly:
``` php
$cur1 = Currency::new(5); // the amount is set to $5.00 upon instantiation
$cur2 = $cur1->val(10);   // and is then set to $10.00 (it's immutable so a new object is created)
```

The types of values you can pass to Currency are:

``` php
$cur1 = Currency::new(5);      // an integer
$cur2 = Currency::new(5.5);    // a float
$cur3 = Currency::new('6.78'); // a numeric string
$cur4 = Currency::new($cur3);  // another Currency object
$cur5 = Currency::new(null);   // null
$cur6 = Currency::new();       // (will default to null)
```

***TIP:*** To maintain precision when passing values, pass them as strings instead of floating-point numbers:

``` php
$cur = Currency::new()->customDecPl(20)->val(0.12345678901234567890);   // "0.12345678901235" (precision lost because the number passed is really a float)
$cur = Currency::new()->customDecPl(20)->val('0.12345678901234567890'); // "0.12345678901234567890" (passed as a string)
```

You may also set other settings that Currency uses:

``` php
$cur = Currency::new();
$cur = $cur->locale('en-US');              // sets the locale this object uses (see the 'locale' section below)
$cur = $cur->curCode('NZD');               // change the currency used
$cur = $cur->customDecPl(30);              // sets the number of decimal places used (see the 'precision (custom decimal places)' section below)
$cur = $cur->useCurrencyDecPl();           // uses the current currency's decimal places again
$cur = $cur->immutable(false);             // sets whether this object is immutable or not (see the 'immutability' section below)
$cur = $cur->formatSettings('!thousands'); // alters the default options used when format() is called (see the 'formatting output' section below)
```

### Retrieving values

To retrieve the value contained in a Currency object you may read the `val` and `cast` properties. The `val` property maintains precision and in contrast, `cast` will loose some precision so use them depending on your needs:

``` php
$cur = Currency::new('0.12345678901234567890');
print $cur->val;  // "0.12345678901234567890" (returned as a string, or null)
print $cur->cast; // 0.12345678901235 (cast to either an integer, float or null - this is less accurate)
```

These properties associated to the currency may be read:

```php
$cur = Currency::new(1);
print $cur->curCode; // USD (the current currency code)
print $cur->symbol;  // $ (the currency symbol in the current locale)
print $cur->decPl;   // 2 (the number of decimal places in the current currency)
```

You may also read other settings that Currency uses:

``` php
$cur = Currency::new();
print $cur->customDecPl;       // null (see the 'precision (custom decimal places)' section below)
print $cur->usingCustomDecPl;  // false (see the 'precision (custom decimal places)' section below)
print $cur->locale;            // "en"
print $cur->immutable;         // true
```

And you can also obtain each currency's symbol:

```php
print Currency::symbol('USD');          // "$" (will pick-up the current default locale 'en')
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
$cur = Currency::new(5);
$cur = $cur->inc();    // increment
$cur = $cur->dec();    // decrement
$cur = $cur->add(2);   // add x
$cur = $cur->sub(2);   // subtract x
$cur = $cur->div(2);   // divide by x
$cur = $cur->mul(2);   // multiply by x
$cur = $cur->round();  // round to zero decimal places
$cur = $cur->round(2); // round to x decimal places
$cur = $cur->floor();  // use the floor of the current value
$cur = $cur->ceil();   // use the ceiling of the current value
```

You may pass multiple values to `add()`, `sub()`, `div()` and `mul()`:

```php
Currency::new(5)->add(4, 3, 2, 1); // $15.00
Currency::new(5)->sub(4, 3, 2, 1); // -$5.00
Currency::new(5)->customDecPl(15)->div(4, 3, 2, 1); // $0.208333333333333
Currency::new(5)->mul(4, 3, 2, 1); // $120.00
```

You may pass: *integer*, *float*, *numeric string* and *null* values, as well as other *Currency* objects.

```php
$cur1 = Currency::new(5);
$cur1 = $cur1->add(2);      // pass an integer
$cur1 = $cur1->add(2.0);    // pass a float
$cur1 = $cur1->add('2.34'); // pass a numeric string
$cur1 = $cur1->add(null);   // pass null (adds nothing)
$cur2 = Currency::new(2);
$cur1 = $cur1->add($cur2);  // pass another Currency object
```

### Comparisons

You can compare amounts to others with bound checking:

``` php
Currency::new(5)->lessThan(10);             // alias of lt(..)
Currency::new(5)->lessThanOrEqualTo(10);    // alias of lte(..)
Currency::new(5)->equalTo(10);              // alias of eq(..)
Currency::new(5)->greaterThanOrEqualTo(10); // alias of gte(..)
Currency::new(5)->greaterThan(10);          // alias of gt(..)

$cur1 = Currency::new(5);
$cur2 = Currency::new(10);
$cur1->lt($cur2); // you can compare a Currency with others
```

You may pass multiple values to these comparison methods. eg.

``` php
Currency::new(5)->lt(10, 15, 20); // will return true if 5 is less-than 10, 15 and 20
```

You can check if a Currency's value is between given bounds:

``` php
Currency::new(5)->between(2, 8);        // check if 5 is between x and y (inclusively)
Currency::new(5)->between(2, 8, false); // check if 5 is between x and y (NOT inclusively)
```

### Formatting output

Use the `format()` method to generate a readable-string version of the current value:

``` php
$cur = Currency::new(1234567.89);
print $cur->format(); // "$1,234,567.89"
```

You may alter the way `format()` renders the output by passing options. The options you can alter are:

`null=x`, `noZeros`, `decPl=x`, `symbol`, `thousands`, `showPlus`, `accountingNeg`, `locale=x` and `breaking`.

Options without a value can be negated by adding `!` before it.

***Note:*** `format()` options are processed using the [code-distortion/options](https://packagist.org/packages/code-distortion/options) package so they may be passed as expressive strings or associative arrays.

``` php
print Currency::new(null)->format('null=null');   // null (actual null - default)
print Currency::new(null)->format('null="null"'); // "null" (returned as a string)
print Currency::new(null)->format('null=0');      // "$0.00"

print Currency::new(1)->format('noZeros');  // "$1" (hides the decimal places when zero)
print Currency::new(1)->format('!noZeros'); // "$1.00" (includes the decimal places - default)

// the amount can be rounded and shown to a specific number of decimal places (this is different to the internal customDecPl setting)
print Currency::new()->customDecPl(20)->val(1.9876)->format('decPl=null'); // "$1.9876" (no rounding - default)
print Currency::new()->customDecPl(20)->val(1.9876)->format('decPl=0');    // "$2" (rounded and shown to 0 decimal places)
print Currency::new()->customDecPl(20)->val(1.9876)->format('decPl=1');    // "$2.0" (rounded and shown to 1 decimal place)
print Currency::new()->customDecPl(20)->val(1.9876)->format('decPl=2');    // "$1.99" (rounded and shown to 2 decimal places)
print Currency::new()->customDecPl(20)->val(1.9876)->format('decPl=6');    // "$1.987600" (rounded and shown to 6 decimal places)
// the extra trailing zeros can be removed again with !trailZeros - but only if the decimal part is zero
print Currency::new()->customDecPl(20)->val(1.9876)->format('decPl=6 !trailZeros'); // "$1.987600" (still shown to 6 decimal places)
print Currency::new()->customDecPl(20)->val(1)->format('decPl=6 !trailZeros');      // "$1" (the trailing zeros removed)

print Currency::new(123.45)->format('symbol');  // "$123.45" (default)
print Currency::new(123.45)->format('!symbol'); // "123.45" (removes the currency symbol)

print Currency::new(1234567.89)->format('thousands');  // "$1,234,567.89" (default)
print Currency::new(1234567.89)->format('!thousands'); // "$1234567.89" (removes the thousands separator)

print Currency::new(123.45)->format('showPlus');  // "+$123.45" (adds a '+' for positive values)
print Currency::new(123.45)->format('!showPlus'); // "$123.45" (default)

print Currency::new(-123.45)->format('accountingNeg');  // "($123.45)" (accounting negative - uses brackets for negative numbers)
print Currency::new(-123.45)->format('!accountingNeg'); // "-$123.45" (default)

// the locale can be chosen at the time of formatting - see the 'local' section below for more details
print Currency::new(1234567.89)->format('locale=en');    // "$1,234,567.89" (English - default)
print Currency::new(1234567.89)->format('locale=en-AU'); // "USD 1,234,567.89" (Australian English)
print Currency::new(1234567.89)->format('locale=en-IN'); // "US$ 12,34,567.89" (Indian English)
print Currency::new(1234567.89)->format('locale=de');    // "1.234.567,89 $" (German)
print Currency::new(1234567.89)->format('locale=sv');    // "1 234 567,89 US$" (Swedish)
print Currency::new(1234567.89)->format('locale=ar');    // "١٬٢٣٤٬٥٦٧٫٨٩ US$"" (Arabic)

// non-breaking spaces can be returned instead of regular spaces - see the 'non-breaking whitespace' section below for more details
print htmlentities(Currency::new(1234567.89)->format('locale=sv-SE !breaking')); // "1&nbsp;234&nbsp;567,89&nbsp;US$" (default)
print htmlentities(Currency::new(1234567.89)->format('locale=sv-SE breaking'));  // "1 234 567,89 US$" (regular spaces)

// the current currency symbol
print Currency::new()->symbol; // "$"
```

Multiple settings can be used together:

```php
print Currency::new(1234567.89)->format('!thousands showPlus locale=de-DE'); // "+1234567,89 $"
```

Casting a Currency to a string is equivalent to calling `format()` with no arguments:

```php
print (string) Currency::new(1234567.89); // "$1,234,567.89"
```

***NOTE***: Currency uses PHP's NumberFormatter to render the readable output, which currently has a limitation of being able to only show about 17 digits (including before the decimal place). So `format()`'s output will act a bit strangely if there are too many digits. The number stored inside will maintain it's full accuracy however. You may access the full number by reading the `val` property (see the [retrieving values](#retrieving-values) section above).

### Default format settings

Currency uses these default settings when `format()` is called: `"null=null !noZeros symbol thousands !showPlus !accountingNeg locale=en !breaking"`

***Note:*** When using Laravel you may set this in the package config file. See the [Laravel](#laravel) section below.

***Note:*** `format()` options are processed using the [code-distortion/options](https://packagist.org/packages/code-distortion/options) package so they may be passed as expressive strings or associative arrays. The default options are stored internally as arrays.

These can be adjusted per-object:

``` php
$cur = Currency::new(1234567.89)->formatSettings('!thousands showPlus');
print $cur->format(); // "+$1234567.89" (no thousands separator, show-plus)
```

The default format-settings can be adjusted. All ***new*** Currency objects will start with this setting:

``` php
var_dump(Currency::getDefaultFormatSettings()); // ['null' => null, 'noZeros' => false … ] (default)
Currency::setDefaultFormatSettings('null="NULL" noZeros');
var_dump(Currency::getDefaultFormatSettings()); // ['null' => 'NULL', 'noZeros' => true … ]
```

### Locale

***Note:*** When using Laravel this will be set automatically. See the [Laravel](#laravel) section below.

Currency's default locale is "en" (English) but you can choose which one to use.

You may change the locale per-object:

``` php
$cur1 = Currency::new(1234567.89);
print $cur1->locale;            // "en" (the default)
print $cur1->format();          // "$1,234,567.89"
$cur2 = $cur1->locale('fr-FR'); // (it's immutable so a new object is created)
print $cur2->locale;            // "fr-FR"
print $cur2->format();          // "1 234 567,89 $US"
```

The default locale may be changed. All ***new*** Currency objects will start with this setting:

``` php
Currency::setDefaultLocale('fr-FR');
print Currency::getDefaultLocale(); // "fr-FR"
```

### Precision (custom decimal places)

The number of decimal places the current currency has is used by default (eg. 2 decimal places for USD). But you may specify the number to use for greater precision. This may be useful if you wish to perform some calculations and then round to the nearest cent at the end.

You may change this per-object using `customDecPl()`:

``` php
// without customDecPl
$cur = Currency::new('0.98765'); // this has more decimal places than USD has
print $cur->val;                 // "0.99" (ie. rounded to the default 2 decimal places)
print $cur->decPl;               // 2
print $cur->customDecPl;         // null
print $cur->usingCustomDecPl;    // false

// with customDecPl
$cur = Currency::new()->customDecPl(30)->val('0.123456789012345678901234567890');
print $cur->val;              // "0.123456789012345678901234567890" (the full 30 decimal places)
print $cur->decPl;            // 30
print $cur->customDecPl;      // 30
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
$cur1 = Currency::new(1);
$cur2 = $cur1->add(2); // $cur1 remains unchanged and $cur2 is a new object containing the new value
print $cur1->format(); // "$1.00"
print $cur2->format(); // "$3.00"
```

Immutability may be turned off per-object:

``` php
$cur1 = Currency::new(1)->immutable(false);
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
$cur1 = Currency::new();
$cur2 = $cur1->copy(); // this will return a clone regardless of the immutability setting
```

### Non-breaking whitespace

Some locales use spaces when rendering numbers (eg. Swedish uses spaces for the thousands separator). `format()` can return strings containing either non-breaking whitespace characters,  or regular space characters.

An example of non-breaking whitespace is UTF-8's `\xc2\xa0` character which is used instead of a regular `\x20` space character. There are others like `\xe2\x80\xaf` which is a 'narrow no-break space'.

The `\xc2\xa0` UTF-8 character will become the familiar `&nbsp;` when turned into an html-entity.

Because `format()` is designed to produce readable numbers for humans, Currency uses non-breaking whitespace by default, but you can instruct it to return regular spaces:

``` php
$cur = Currency::new(1234567.89)->locale('sv-SE'); // Swedish
print htmlentities($cur->format('!breaking'));     // "1&nbsp;234&nbsp;567,89&nbsp;US$" (contains non-breaking whitespace - default)
print htmlentities($cur->format('breaking'));      // "1 234 567,89 US$" (regular spaces)
```

***Tip:*** The non-breaking whitespace setting can be changed per-object and by default. See the [formatting output](#formatting-output) and [default format settings](#default-format-settings) sections above.

### Chaining

The *setting* and *calculation* methods above may be chained together:

``` php
print Currency::new(1)
->locale('en-US')->val(5)->customDecPl(3) // some "setting" methods
->add(4)->mul(3)->div(2)->sub(1)          // some "calculation" methods
->format(); // "$12.50"
```

### Laravel

The Currency package is framework agnostic and works well on it's own, but it also integrates with Laravel 5 & 6.

#### Service-provider

Currency integrates with Laravel 5.5+ automatically thanks to Laravel's package auto-detection. For Laravel 5.0 - 5.4, add the following line to **config/app.php**:

``` php
'providers' => [
    …
    CodeDistortion\Currency\Laravel\ServiceProvider::class,
    …
],
```

The service-provider will register the starting locale with Currency and update it if it changes, so you don't have to.

#### Config

You may specify default immutability and format-settings by publishing the **config/currency.php** config file and updating it:

``` bash
php artisan vendor:publish --provider="CodeDistortion\Currency\Laravel\ServiceProvider" --tag="config"
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

### SemVer

This library uses [SemVer 2.0.0](https://semver.org/) versioning. This means that changes to `X` indicate a breaking change: `0.0.X`, `0.X.y`, `X.y.z`. When this library changes to version 1.0.0, 2.0.0 and so forth it doesn't indicate that it's necessarily a notable release, it simply indicates that the changes were breaking.

## Treeware

You're free to use this package, but if it makes it to your production environment please plant or buy a tree for the world.

It's now common knowledge that one of the best tools to tackle the climate crisis and keep our temperatures from rising above 1.5C is to <a href="https://www.bbc.co.uk/news/science-environment-48870920">plant trees</a>. If you support this package and contribute to the Treeware forest you'll be creating employment for local families and restoring wildlife habitats.

You can buy trees here [offset.earth/treeware](https://offset.earth/treeware?gift-trees)

Read more about Treeware at [treeware.earth](http://treeware.earth)

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
