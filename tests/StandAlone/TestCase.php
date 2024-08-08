<?php

namespace CodeDistortion\Currency\Tests\StandAlone;

// phpunit added namespacing to its classes in >= 5.5
// this code compensates for the fact that older versions of Laravel Orchestra Testbench (which are also part of the
// test suite) require an older version of phpunit
if (class_exists(\PHPUnit\Framework\TestCase::class)) {
    class_alias(\PHPUnit\Framework\TestCase::class, BaseTestCase::class);
} else {
    class_alias(\PHPUnit_Framework_TestCase::class, BaseTestCase::class);
}

//use PHPUnit\Framework\TestCase as BaseTestCase;
use Jchook\AssertThrows\AssertThrows;

/**
 * The test case that unit tests extend from.
 */
class TestCase extends BaseTestCase
{
    use AssertThrows;
}
