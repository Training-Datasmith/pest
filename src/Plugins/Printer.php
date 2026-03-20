<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Handles_Arguments;
/**
 * @internal
 */
final class Printer implements Handles_Arguments
{
    use Concerns\Handle_Arguments;
    /**
     * {@inheritDoc}
     */
    public function handle_arguments(array $arguments): array
    {
        if (!array_key_exists('COLLISION_PRINTER', $_SERVER)) {
            return $arguments;
        }
        if (in_array('--no-output', $arguments, true)) {
            return $arguments;
        }
        return $this->push_argument('--no-output', $arguments);
    }
}