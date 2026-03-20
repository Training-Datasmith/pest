<?php

declare(strict_types=1);

namespace Tests\Fixtures\Inheritance;

class ExampleTest extends Base\ExampleTest
{
    protected $foo;

    public function test_example()
    {
        $this->assertTrue(true);
    }
}
