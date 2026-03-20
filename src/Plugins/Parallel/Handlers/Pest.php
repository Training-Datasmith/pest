<?php

declare (strict_types=1);
namespace Pest\Plugins\Parallel\Handlers;

use Pest\Plugins\Concerns\Handle_Arguments;
use Pest\Plugins\Parallel\Contracts\Handlers_Worker_Arguments;
final class Pest implements Handlers_Worker_Arguments
{
    use Handle_Arguments;
    /**
     * Handles the arguments, adding the "PEST_PARALLEL" environment variable to the global $_SERVER.
     */
    public function handle_worker_arguments(array $arguments): array
    {
        $_SERVER['PEST_PARALLEL'] = '1';
        return $arguments;
    }
}