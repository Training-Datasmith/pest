<?php

declare (strict_types=1);
namespace Pest\Contracts;

use Pest\Factories\Test_Case_Method_Factory;
interface Test_Case_Method_Filter
{
    /**
     * Whether the test case method is accepted.
     */
    public function accept(Test_Case_Method_Factory $factory): bool;
}