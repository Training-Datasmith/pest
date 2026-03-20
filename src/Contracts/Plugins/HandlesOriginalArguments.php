<?php

declare (strict_types=1);
namespace Pest\Contracts\Plugins;

/**
 * @internal
 */
interface Handles_Original_Arguments
{
    /**
     * Adds original arguments before the Test Suite execution.
     *
     * @param  array<int, string>  $arguments
     */
    public function handle_original_arguments(array $arguments): void;
}