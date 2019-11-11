<?php

namespace CodeDistortion\Currency\Exceptions;

use CodeDistortion\RealNum\Exceptions\InvalidArgumentException as BaseInvalidArgumentException;

/**
 * Exception for when invalid arguments are passed
 */
class InvalidArgumentException extends BaseInvalidArgumentException
{
    /**
     * Return a new instance when a currency couldn't be resolved
     *
     * @param string $currencyIdentifier The currency being resolved.
     * @return static
     */
    public static function unresolveableCurrency(string $currencyIdentifier): self
    {
        return new static('Currency "'.$currencyIdentifier.'" could not be resolved');
    }

    /**
     * Return a new instance when an incompatible currency is used
     *
     * @param mixed $currency1 The first currency.
     * @param mixed $currency2 The second currency.
     * @return static
     */
    public static function incompatibleCurrencies($currency1, $currency2): self
    {
        return new static('Currency code '.$currency1.' is not compatible with '.$currency2);
    }

    /**
     * Return a new instance when no currency has been specified
     *
     * @return static
     */
    public static function currencyNotSpecified(): self
    {
        return new static('Currency-code was not specified. Please pass one or specify a default');
    }
}
