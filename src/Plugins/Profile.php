<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Exceptions\Invalid_Option;
/**
 * @internal
 */
final class Profile implements Handles_Arguments
{
    use Concerns\Handle_Arguments;
    /**
     * {@inheritDoc}
     */
    public function handle_arguments(array $arguments): array
    {
        if (!$this->has_argument('--profile', $arguments)) {
            return $arguments;
        }
        if ($this->has_argument('--parallel', $arguments)) {
            throw new Invalid_Option('The [--profile] option is not supported when running in parallel.');
        }
        return $arguments;
    }
}