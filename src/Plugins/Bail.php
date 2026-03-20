<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Plugins\Concerns\Handle_Arguments;
/**
 * @internal
 */
final class Bail implements Handles_Arguments
{
    use Handle_Arguments;
    /**
     * Handles the arguments, adding the `--stop-on-defect` when the `--bail` argument is present.
     */
    public function handle_arguments(array $arguments): array
    {
        if ($this->has_argument('--bail', $arguments)) {
            $arguments = $this->pop_argument('--bail', $arguments);
            $arguments = $this->push_argument('--stop-on-failure', $arguments);
            $arguments = $this->push_argument('--stop-on-error', $arguments);
        }
        return $arguments;
    }
}