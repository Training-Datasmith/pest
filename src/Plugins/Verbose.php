<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Handles_Arguments;
/**
 * @internal
 */
final class Verbose implements Handles_Arguments
{
    use Concerns\Handle_Arguments;
    /**
     * The list of verbosity levels.
     */
    private const array VERBOSITY_LEVELS = ['v', 'vv', 'vvv', 'q'];
    /**
     * {@inheritDoc}
     */
    public function handle_arguments(array $arguments): array
    {
        foreach (self::VERBOSITY_LEVELS as $level) {
            if ($this->has_argument('-' . $level, $arguments)) {
                $arguments = $this->pop_argument('-' . $level, $arguments);
            }
        }
        if ($this->has_argument('--quiet', $arguments)) {
            return $this->pop_argument('--quiet', $arguments);
        }
        return $arguments;
    }
}