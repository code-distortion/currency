<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default currency
    |--------------------------------------------------------------------------
    |
    | This is the default currency code that Currency will use for new objects.
    | When it's left blank, the currency code will need to be specified for
    | each Currency object.
    |
    | eg. 'USD'
    |
    */

    'default_currency_code' => null,

    /*
    |--------------------------------------------------------------------------
    | Immutability
    |--------------------------------------------------------------------------
    |
    | This value determines whether Currency is immutable by default or not.
    |
    */

    'immutable' => true,

    /*
    |--------------------------------------------------------------------------
    | Format settings
    |--------------------------------------------------------------------------
    |
    | Currency will use these default settings when format() is called. You
    | can adjust these by adding values to the string below. You may choose
    | from the possible values below.
    |
    | default: 'null=null decPl=null trailZeros symbol thousands !showPlus !accountingNeg !breaking'
    |
    */

    'format_settings' => null,

];
