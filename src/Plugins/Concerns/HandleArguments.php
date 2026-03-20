<?php

declare (strict_types=1);
namespace Pest\Plugins\Concerns;

/**
 * @internal
 */
trait Handle_Arguments
{
    /**
     * Checks if the given argument exists on the arguments.
     *
     * @param  array<int, string>  $arguments
     */
    public function has_argument(string $argument, array $arguments): bool
    {
        foreach ($arguments as $arg) {
            if ($arg === $argument) {
                return true;
            }
            if (str_starts_with((string) $arg, "{$argument}=")) {
                // @phpstan-ignore-line
                return true;
            }
        }
        return false;
    }
    /**
     * Adds the given argument and value to the list of arguments.
     *
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    public function push_argument(string $argument, array $arguments): array
    {
        $arguments[] = $argument;
        return $arguments;
    }
    /**
     * Pops the given argument from the arguments.
     *
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    public function pop_argument(string $argument, array $arguments): array
    {
        $arguments = array_flip($arguments);
        unset($arguments[$argument]);
        return array_values(array_flip($arguments));
    }
}