<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Para_Test\Para_Test_Command;
use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Plugins\Actions\Calls_Adds_Output;
use Pest\Plugins\Concerns\Handle_Arguments;
use Pest\Plugins\Parallel\Contracts\Handlers_Worker_Arguments;
use Pest\Plugins\Parallel\Paratest\Clean_Console_Output;
use Pest\Support\Arr;
use Pest\Support\Container;
use Pest\Test_Suite;
use function Pest\version;
use Stringable;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\Argv_Input;
final class Parallel implements Handles_Arguments
{
    use Handle_Arguments;
    private const string GLOBAL_PREFIX = 'PEST_PARALLEL_GLOBAL_';
    private const array HANDLERS = [Parallel\Handlers\Parallel::class, Parallel\Handlers\Pest::class, Parallel\Handlers\Laravel::class];
    /**
     * @var string[]
     */
    private const array UNSUPPORTED_ARGUMENTS = ['--todo', '--todos', '--retry', '--notes', '--issue', '--pr', '--pull-request'];
    /**
     * Whether the given command line arguments indicate that the test suite should be run in parallel.
     */
    public static function is_enabled(): bool
    {
        $argv = new Argv_Input();
        if ($argv->has_parameter_option('--parallel')) {
            return true;
        }
        return $argv->has_parameter_option('-p');
    }
    /**
     * If this code is running in a worker process rather than the main process.
     */
    public static function is_worker(): bool
    {
        $argv_value = Arr::get($_SERVER, 'PARATEST');
        assert(is_string($argv_value) || is_int($argv_value) || is_null($argv_value));
        return (int) $argv_value === 1;
    }
    /**
     * Sets a global value that can be accessed by the parent process and all workers.
     */
    public static function set_global(string $key, string|int|bool|Stringable $value): void
    {
        $data = ['value' => $value instanceof Stringable ? $value->__toString() : $value];
        $_ENV[self::GLOBAL_PREFIX . $key] = json_encode($data, JSON_THROW_ON_ERROR);
    }
    /**
     * Returns the given global value if one has been set.
     */
    public static function get_global(string $key): string|int|bool|null
    {
        $places_to_check = [$_SERVER, $_ENV];
        foreach ($places_to_check as $location) {
            if (array_key_exists(self::GLOBAL_PREFIX . $key, $location)) {
                // @phpstan-ignore-next-line
                return json_decode((string) $location[self::GLOBAL_PREFIX . $key], true, 512, JSON_THROW_ON_ERROR)['value'] ?? null;
            }
        }
        return null;
    }
    /**
     * {@inheritdoc}
     */
    public function handle_arguments(array $arguments): array
    {
        if ($this->has_arguments_that_would_be_faster_without_parallel()) {
            return $this->run_test_suite_in_series($arguments);
        }
        if (self::is_enabled()) {
            exit($this->run_test_suite_in_parallel($arguments));
        }
        if (self::is_worker()) {
            return $this->run_worker_handlers($arguments);
        }
        return $arguments;
    }
    /**
     * Runs the test suite in parallel. This method will exit the process upon completion.
     *
     * @param  array<int, string>  $arguments
     */
    private function run_test_suite_in_parallel(array $arguments): int
    {
        $handlers = array_filter(array_map(fn(string $handler): object|string => Container::get_instance()->get($handler), self::HANDLERS), fn(object|string $handler): bool => $handler instanceof Handles_Arguments);
        $filtered_arguments = array_reduce($handlers, fn(array $arguments, Handles_Arguments $handler): array => $handler->handle_arguments($arguments), $arguments);
        $exit_code = $this->paratest_command()->run(new Argv_Input($filtered_arguments), new Clean_Console_Output());
        return Calls_Adds_Output::execute($exit_code);
    }
    /**
     * Runs any handlers that have been registered to handle worker arguments, and returns the modified arguments.
     *
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function run_worker_handlers(array $arguments): array
    {
        $handlers = array_filter(array_map(fn(string $handler): object|string => Container::get_instance()->get($handler), self::HANDLERS), fn(object|string $handler): bool => $handler instanceof Handlers_Worker_Arguments);
        return array_reduce($handlers, fn(array $arguments, Handlers_Worker_Arguments $handler): array => $handler->handle_worker_arguments($arguments), $arguments);
    }
    /**
     * Builds an instance of the Paratest command.
     */
    private function paratest_command(): Application
    {
        /** @var non-empty-string $rootPath */
        $root_path = Test_Suite::get_instance()->root_path;
        $command = Para_Test_Command::application_factory($root_path);
        $command->set_auto_exit(false);
        $command->set_name('Pest');
        $command->set_version(version());
        return $command;
    }
    /**
     * Whether the command line arguments contain any arguments that are
     * not supported or are suboptimal when running in parallel.
     */
    private function has_arguments_that_would_be_faster_without_parallel(): bool
    {
        $arguments = new Argv_Input();
        foreach (self::UNSUPPORTED_ARGUMENTS as $unsupported_argument) {
            if ($arguments->has_parameter_option($unsupported_argument)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Removes any parallel arguments.
     *
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function run_test_suite_in_series(array $arguments): array
    {
        $arguments = $this->pop_argument('--parallel', $arguments);
        return $this->pop_argument('-p', $arguments);
    }
}