<?php

declare (strict_types=1);
namespace Pest\Exceptions;

use InvalidArgumentException;
use Nuno_Maduro\Collision\Contracts\Renderless_Editor;
use Nuno_Maduro\Collision\Contracts\Renderless_Trace;
use Pest\Factories\Test_Case_Method_Factory;
use Symfony\Component\Console\Exception\Exception_Interface;
/**
 * @internal
 */
final class Test_Closure_Must_Not_Be_Static extends InvalidArgumentException implements Exception_Interface, Renderless_Editor, Renderless_Trace
{
    /**
     * Creates a new Exception instance.
     */
    public function __construct(Test_Case_Method_Factory $method)
    {
        parent::__construct(sprintf('Test closure must not be static. Please remove the [static] keyword from the [%s] method in [%s].', $method->description, $method->filename));
    }
}