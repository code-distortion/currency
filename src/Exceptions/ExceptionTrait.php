<?php

namespace CodeDistortion\Currency\Exceptions;

/**
 * Provide tools for exceptions.
 */
trait ExceptionTrait
{
    /**
     * Return the name of the class that called this one.
     *
     * Thanks to hamstar https://stackoverflow.com/questions/3620923/how-to-get-the-name-of-the-calling-class-in-php .
     *
     * @return string
     */
    protected static function getCallingClass(): string
    {
        // get the trace
        $trace = debug_backtrace();

        // get the class that is asking for who awoke it
        $class = $trace[1]['class'];

        // +1 to i because we have to account for calling this function
        for ($i = 1; $i < count($trace); $i++) {

            // is it set?
            if (!isset($trace[$i])) {
                continue;
            }
            // is it a different class
            if ($class == $trace[$i]['class']) {
                continue;
            }

            return $trace[$i]['class'];
        }
        return '';
    }
}
