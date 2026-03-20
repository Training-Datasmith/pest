<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Exceptions\Invalid_Option;
/**
 * @internal
 */
final class Retry implements Handles_Arguments
{
    use Concerns\Handle_Arguments;
    /**
     * {@inheritDoc}
     */
    public function handle_arguments(array $arguments): array
    {
        if (!$this->has_argument('--retry', $arguments)) {
            return $arguments;
        }
        if ($this->has_argument('--parallel', $arguments)) {
            throw new Invalid_Option('The [--retry] option is not supported when running in parallel.');
        }
        $arguments = $this->pop_argument('--retry', $arguments);
        $arguments = $this->push_argument('--order-by=defects', $arguments);
        return $this->push_argument('--stop-on-failure', $arguments);
    }
}