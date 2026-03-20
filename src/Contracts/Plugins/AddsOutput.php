<?php

declare (strict_types=1);
namespace Pest\Contracts\Plugins;

/**
 * @internal
 */
interface Adds_Output
{
    /**
     * Adds output after the Test Suite execution.
     */
    public function add_output(int $exit_code): int;
}