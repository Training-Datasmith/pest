<?php

declare (strict_types=1);
namespace Pest\Exceptions;

use LogicException;
use Nuno_Maduro\Collision\Contracts\Renderless_Editor;
use Nuno_Maduro\Collision\Contracts\Renderless_Trace;
use Symfony\Component\Console\Exception\Exception_Interface;
/**
 * @internal
 */
final class Invalid_Expectation extends LogicException implements Exception_Interface, Renderless_Editor, Renderless_Trace
{
    /**
     * @param  array<int, string>  $methods
     *
     * @throws self
     */
    public static function from_methods(array $methods): never
    {
        throw new self(sprintf('Expectation [%s] is not valid.', implode('->', $methods)));
    }
}