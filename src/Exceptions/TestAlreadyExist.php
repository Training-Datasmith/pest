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
final class Test_Already_Exist extends InvalidArgumentException implements Exception_Interface, Renderless_Editor, Renderless_Trace
{
    /**
     * Creates a new Exception instance.
     */
    public function __construct(string $file_name, string $description)
    {
        parent::__construct(sprintf('A test with the description `%s` already exists in the filename `%s`.', $description, $file_name));
    }
}