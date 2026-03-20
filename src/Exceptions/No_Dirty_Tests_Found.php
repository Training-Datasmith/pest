<?php

declare (strict_types=1);
namespace Pest\Exceptions;

use InvalidArgumentException;
use Nuno_Maduro\Collision\Contracts\Renderless_Editor;
use Nuno_Maduro\Collision\Contracts\Renderless_Trace;
use Pest\Contracts\Panicable;
use Symfony\Component\Console\Exception\Exception_Interface;
use Symfony\Component\Console\Output\Output_Interface;
/**
 * @internal
 */
final class No_Dirty_Tests_Found extends InvalidArgumentException implements Exception_Interface, Panicable, Renderless_Editor, Renderless_Trace
{
    /**
     * Renders the panic on the given output.
     */
    public function render(Output_Interface $output): void
    {
        $output->writeln(['', '  <fg=white;options=bold;bg=blue> INFO </> No "dirty" tests found.', '']);
    }
    /**
     * The exit code to be used.
     */
    public function exit_code(): int
    {
        return 0;
    }
}