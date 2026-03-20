<?php

declare (strict_types=1);
namespace Pest\Contracts;

use Symfony\Component\Console\Output\Output_Interface;
/**
 * @internal
 */
interface Panicable
{
    /**
     * Renders the panic on the given output.
     */
    public function render(Output_Interface $output): void;
    /**
     * The exit code to be used.
     */
    public function exit_code(): int;
}