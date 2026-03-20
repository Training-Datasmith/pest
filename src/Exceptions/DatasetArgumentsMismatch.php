<?php

declare (strict_types=1);
namespace Pest\Exceptions;

use Exception;
final class Dataset_Arguments_Mismatch extends Exception
{
    public function __construct(int $required_count, int $supplied_count)
    {
        if ($required_count <= $supplied_count) {
            parent::__construct('Test argument names and dataset keys do not match');
        } else {
            parent::__construct(sprintf('Test expects %d arguments but dataset only provides %d', $required_count, $supplied_count));
        }
    }
}