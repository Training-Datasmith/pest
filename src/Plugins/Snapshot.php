<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Exceptions\Invalid_Option;
use Pest\Test_Suite;
/**
 * @internal
 */
final class Snapshot implements Handles_Arguments
{
    use Concerns\Handle_Arguments;
    /**
     * {@inheritDoc}
     */
    public function handle_arguments(array $arguments): array
    {
        if (!$this->has_argument('--update-snapshots', $arguments)) {
            return $arguments;
        }
        if ($this->has_argument('--parallel', $arguments)) {
            throw new Invalid_Option('The [--update-snapshots] option is not supported when running in parallel.');
        }
        Test_Suite::get_instance()->snapshots->flush();
        return $this->pop_argument('--update-snapshots', $arguments);
    }
}