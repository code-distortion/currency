<?php

namespace CodeDistortion\RealNum\Exceptions;

use CodeDistortion\RealNum\Exceptions\ExceptionTrait;
use Exception;

/**
 * Exception for when undefined properties are accessed
 */
class UndefinedPropertyException extends Exception
{
    use ExceptionTrait;

    /**
     * Return a new instance when a undefined property was accessed
     *
     * @param string $name The name of the unexpected property being accessed.
     * @return static
     */
    public static function new(string $name): self
    {
        return new static('Undefined property: '.static::getCallingClass().'::$'.$name);
    }
}
