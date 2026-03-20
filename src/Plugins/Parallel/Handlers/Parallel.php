<?php

declare (strict_types=1);
namespace Pest\Plugins\Parallel\Handlers;

use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Plugins\Concerns\Handle_Arguments;
use Pest\Plugins\Parallel\Paratest\Wrapper_Runner;
/**
 * @internal
 */
final class Parallel implements Handles_Arguments
{
    use Handle_Arguments;
    /**
     * The list of arguments to remove.
     */
    private const array ARGS_TO_REMOVE = ['--parallel', '-p', '--no-output', '--cache-result'];
    /**
     * Handles the arguments, removing the ones that are not needed, and adds the "runner" argument.
     */
    public function handle_arguments(array $arguments): array
    {
        $args = array_reduce(self::ARGS_TO_REMOVE, fn(array $args, string $arg): array => $this->pop_argument($arg, $args), $arguments);
        return $this->push_argument('--runner=' . Wrapper_Runner::class, $args);
    }
}