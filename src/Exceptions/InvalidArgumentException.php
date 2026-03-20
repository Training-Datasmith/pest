<?php

declare (strict_types=1);
namespace Pest\Exceptions;

use InvalidArgumentException as BaseInvalidArgumentException;
use Nuno_Maduro\Collision\Contracts\Renderless_Editor;
use Nuno_Maduro\Collision\Contracts\Renderless_Trace;
use Symfony\Component\Console\Exception\Exception_Interface;
/**
 * @internal
 */
final class InvalidArgumentException extends Base_Invalid_Argument_Exception implements Exception_Interface, Renderless_Editor, Renderless_Trace
{
    /**
     * Creates a new Exception instance.
     */
    public function __construct(string $message)
    {
        parent::__construct($message, 1);
    }
}