<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * @return TestCase
 */
function myAssertTrue($value)
{
    test()->assertTrue($value);

    return test();
}
