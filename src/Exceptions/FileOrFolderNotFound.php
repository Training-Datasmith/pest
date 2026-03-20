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
final class File_Or_Folder_Not_Found extends InvalidArgumentException implements Exception_Interface, Renderless_Editor, Renderless_Trace
{
    /**
     * Creates a new Exception instance.
     */
    public function __construct(string $filename)
    {
        parent::__construct(sprintf('The file or folder with the name `%s` could not be found.', $filename));
    }
}