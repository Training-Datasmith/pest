<?php

declare (strict_types=1);
namespace Pest\Contracts;

interface Test_Case_Filter
{
    /**
     * Whether the test case is accepted.
     */
    public function accept(string $test_case_filename): bool;
}