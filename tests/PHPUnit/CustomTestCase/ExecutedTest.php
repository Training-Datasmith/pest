<?php

declare(strict_types=1);

namespace Tests\CustomTestCase;

use function PHPUnit\Framework\assertTrue;

use PHPUnit\Framework\TestCase;

class ExecutedTest extends TestCase
{
    public static $executed = false;

    #[Test]
    public function test_that_gets_executed(): void
    {
        self::$executed = true;

        assertTrue(true);
    }
}

// register_shutdown_function(fn () => assertTrue(ExecutedTest::$executed));
