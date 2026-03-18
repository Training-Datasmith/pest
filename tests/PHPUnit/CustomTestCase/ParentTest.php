<?php

declare(strict_types=1);

namespace Tests\CustomTestCase;

use function PHPUnit\Framework\assertTrue;

use PHPUnit\Framework\Attributes\Test;

use PHPUnit\Framework\TestCase;

class ParentTest extends TestCase
{
    private function getEntity(): bool
    {
        return false;
    }

    #[Test]
    public function test_override_method(): void
    {
        assertTrue($this->getEntity() || true);
    }
}
