<?php

declare (strict_types=1);
namespace Pest\Test_Case_Method_Filters;

use Pest\Contracts\Test_Case_Method_Filter;
use Pest\Factories\Test_Case_Method_Factory;
final readonly class Pr_Test_Case_Filter implements Test_Case_Method_Filter
{
    /**
     * Create a new filter instance.
     */
    public function __construct(private int $number)
    {
    }
    /**
     * Filter the test case methods.
     */
    public function accept(Test_Case_Method_Factory $factory): bool
    {
        return in_array($this->number, $factory->prs, true);
    }
}