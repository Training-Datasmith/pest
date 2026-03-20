<?php

declare (strict_types=1);
namespace Pest\Runner\Filter;

use Pest\Contracts\Has_Printable_Test_Case_Name;
use Recursive_Filter_Iterator;
/**
 * @internal
 */
final class Ensure_Test_Case_Is_Initiated_Filter extends Recursive_Filter_Iterator
{
    /**
     * {@inheritdoc}
     */
    public function accept(): bool
    {
        $test = $this->get_inner_iterator()->current();
        if ($test instanceof Has_Printable_Test_Case_Name) {
            /** @phpstan-ignore-next-line */
            $test->__initialize_test_case();
        }
        return true;
    }
}