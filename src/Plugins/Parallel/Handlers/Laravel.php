<?php

declare (strict_types=1);
namespace Pest\Plugins\Parallel\Handlers;

use Closure;
use Composer\Installed_Versions;
use Illuminate\Testing\Parallel_Runner;
use Orchestra\Testbench\Test_Case;
use Para_Test\Options;
use Para_Test\Runner_Interface;
use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Plugins\Concerns\Handle_Arguments;
use Pest\Plugins\Parallel\Paratest\Wrapper_Runner;
use Symfony\Component\Console\Output\Output_Interface;
/**
 * @internal
 */
final class Laravel implements Handles_Arguments
{
    use Handle_Arguments;
    /**
     * {@inheritdoc}
     */
    public function handle_arguments(array $arguments): array
    {
        return $this->when_using_laravel($arguments, function (array $arguments): array {
            $this->ensure_runner_is_resolvable();
            $arguments = $this->ensure_environment_variables($arguments);
            return $this->ensure_runner($arguments);
        });
    }
    /**
     * Executes the given closure when running Laravel.
     *
     * @param  array<int, string>  $arguments
     * @param  Closure(array<int, string>): array<int, string>  $closure
     * @return array<int, string>
     */
    private function when_using_laravel(array $arguments, Closure $closure): array
    {
        $is_laravel_application = Installed_Versions::is_installed('laravel/framework', false);
        $is_laravel_package = class_exists(Test_Case::class);
        if ($is_laravel_application && !$is_laravel_package) {
            return $closure($arguments);
        }
        return $arguments;
    }
    /**
     * Ensures the runner is resolvable.
     */
    private function ensure_runner_is_resolvable(): void
    {
        Parallel_Runner::resolve_runner_using(
            // @phpstan-ignore-line
            fn(Options $options, Output_Interface $output): Runner_Interface => new Wrapper_Runner($options, $output)
        );
    }
    /**
     * Ensures the environment variables are set.
     *
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function ensure_environment_variables(array $arguments): array
    {
        $_ENV['LARAVEL_PARALLEL_TESTING'] = 1;
        if ($this->has_argument('--recreate-databases', $arguments)) {
            $_ENV['LARAVEL_PARALLEL_TESTING_RECREATE_DATABASES'] = 1;
        }
        if ($this->has_argument('--drop-databases', $arguments)) {
            $_ENV['LARAVEL_PARALLEL_TESTING_DROP_DATABASES'] = 1;
        }
        $arguments = $this->pop_argument('--recreate-databases', $arguments);
        return $this->pop_argument('--drop-databases', $arguments);
    }
    /**
     * Ensure the runner is set.
     *
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function ensure_runner(array $arguments): array
    {
        foreach ($arguments as $value) {
            if (str_starts_with($value, '--runner')) {
                $arguments = $this->pop_argument($value, $arguments);
            }
        }
        return $this->push_argument('--runner=\Illuminate\Testing\ParallelRunner', $arguments);
    }
}