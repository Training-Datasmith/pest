<?php

declare (strict_types=1);
namespace Pest\Plugins\Parallel\Contracts;

interface Handlers_Worker_Arguments
{
    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    public function handle_worker_arguments(array $arguments): array;
}