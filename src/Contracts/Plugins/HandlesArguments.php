<?php

declare (strict_types=1);
namespace Pest\Contracts\Plugins;

/**
 * @internal
 */
interface Handles_Arguments
{
    /**
     * Adds arguments before the Test Suite execution.
     *
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    public function handle_arguments(array $arguments): array;
}