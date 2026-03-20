<?php

declare (strict_types=1);
namespace Pest\Test_Case_Method_Filters;

use Pest\Contracts\Test_Case_Method_Filter;
use Pest\Factories\Test_Case_Method_Factory;
final readonly class Todo_Test_Case_Filter implements Test_Case_Method_Filter
{
    /**
     * Filter the test case methods.
     */
    public function accept(Test_Case_Method_Factory $factory): bool
    {
        return $factory->todo;
    }
}