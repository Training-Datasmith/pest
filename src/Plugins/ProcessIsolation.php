<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Exceptions\Invalid_Option;
/**
 * @internal
 */
final class Process_Isolation implements Handles_Arguments
{
    use Concerns\Handle_Arguments;
    /**
     * {@inheritDoc}
     */
    public function handle_arguments(array $arguments): array
    {
        if ($this->has_argument('--process-isolation', $arguments)) {
            throw new Invalid_Option('The [--process-isolation] option is not supported.');
        }
        return $arguments;
    }
}