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
final class Invalid_Pest_Command extends InvalidArgumentException implements Exception_Interface, Renderless_Editor, Renderless_Trace
{
    /**
     * Creates a new Exception instance.
     */
    public function __construct()
    {
        parent::__construct('Please run [./vendor/bin/pest] instead.');
    }
}