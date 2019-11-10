<?php

return [

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
    | default: 'symbol thousards !showPlus !accountingNeg !nullString !nullZero !noZeros !breaking'
    |
    */

    'format_settings' => null,

];
