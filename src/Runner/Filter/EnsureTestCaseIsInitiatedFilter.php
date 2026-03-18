<?php

declare(strict_types=1);

namespace Pest\Runner\Filter;

use Pest\Contracts\HasPrintableTestCaseName;
use RecursiveFilterIterator;

/**
 * @internal
 */
final class EnsureTestCaseIsInitiatedFilter extends RecursiveFilterIterator
{
    /**
     * {@inheritdoc}
     */
    public function accept(): bool
    {
        $test = $this->getInnerIterator()->current();

        if ($test instanceof HasPrintableTestCaseName) {
            /** @phpstan-ignore-next-line */
            $test->__initializeTestCase();
        }

        return true;
    }
}
