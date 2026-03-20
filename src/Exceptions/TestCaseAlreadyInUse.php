<?php

declare (strict_types=1);
namespace Pest\Exceptions;

use InvalidArgumentException;
use Nuno_Maduro\Collision\Contracts\Renderless_Editor;
use Nuno_Maduro\Collision\Contracts\Renderless_Trace;
use Symfony\Component\Console\Exception\Exception_Interface;
/**
 * @internal
 */
final class Test_Case_Already_In_Use extends InvalidArgumentException implements Exception_Interface, Renderless_Editor, Renderless_Trace
{
    /**
     * Creates a new Exception instance.
     */
    public function __construct(string $in_use, string $new_one, string $folder)
    {
        parent::__construct(sprintf('Test case [%s] can not be used. The folder [%s] already uses the test case [%s].', $new_one, $folder, $in_use));
    }
}