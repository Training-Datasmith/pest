<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Adds_Output;
use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Exceptions\Invalid_Option;
use Symfony\Component\Console\Input\Argv_Input;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
use Symfony\Component\Process\Process;
/**
 * @internal
 */
final class Shard implements Adds_Output, Handles_Arguments
{
    use Concerns\Handle_Arguments;
    private const string SHARD_OPTION = 'shard';
    /**
     * The shard index and total number of shards.
     *
     * @var array{
     *     index: int,
     *     total: int,
     *     testsRan: int,
     *     testsCount: int
     * }|null
     */
    private static ?array $shard = null;
    /**
     * Creates a new Plugin instance.
     */
    public function __construct(private readonly Output_Interface $output)
    {
    }
    /**
     * {@inheritDoc}
     */
    public function handle_arguments(array $arguments): array
    {
        if (!$this->has_argument('--shard', $arguments)) {
            return $arguments;
        }
        // @phpstan-ignore-next-line
        $input = new Argv_Input($arguments);
        ['index' => $index, 'total' => $total] = self::get_shard($input);
        $arguments = $this->pop_argument("--shard={$index}/{$total}", $this->pop_argument('--shard', $this->pop_argument("{$index}/{$total}", $arguments)));
        /** @phpstan-ignore-next-line */
        $tests = $this->all_tests($arguments);
        $tests_to_run = array_chunk($tests, max(1, (int) ceil(count($tests) / $total)))[$index - 1] ?? [];
        self::$shard = ['index' => $index, 'total' => $total, 'testsRan' => count($tests_to_run), 'testsCount' => count($tests)];
        return [...$arguments, '--filter', $this->build_filter_argument($tests_to_run)];
    }
    /**
     * Returns all tests that the test suite would run.
     *
     * @param  list<string>  $arguments
     * @return list<string>
     */
    private function all_tests(array $arguments): array
    {
        $output = (new Process(['php', ...$this->remove_parallel_arguments($arguments), '--list-tests']))->must_run()->get_output();
        preg_match_all('/ - (?:P\\\\)?(Tests\\\\[^:]+)::/', $output, $matches);
        return array_values(array_unique($matches[1]));
    }
    /**
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    private function remove_parallel_arguments(array $arguments): array
    {
        return array_filter($arguments, fn(string $argument): bool => !in_array($argument, ['--parallel', '-p'], strict: true));
    }
    /**
     * Builds the filter argument for the given tests to run.
     */
    private function build_filter_argument(mixed $tests_to_run): string
    {
        return addslashes(implode('|', $tests_to_run));
    }
    /**
     * Adds output after the Test Suite execution.
     */
    public function add_output(int $exit_code): int
    {
        if (self::$shard === null) {
            return $exit_code;
        }
        ['index' => $index, 'total' => $total, 'testsRan' => $tests_ran, 'testsCount' => $tests_count] = self::$shard;
        $this->output->writeln(sprintf('  <fg=gray>Shard:</>    <fg=default>%d of %d</> — %d file%s ran, out of %d.', $index, $total, $tests_ran, $tests_ran === 1 ? '' : 's', $tests_count));
        return $exit_code;
    }
    /**
     * Returns the shard information.
     *
     * @return array{index: int, total: int}
     */
    public static function get_shard(Input_Interface $input): array
    {
        if ($input->has_parameter_option('--' . self::SHARD_OPTION)) {
            $shard = $input->get_parameter_option('--' . self::SHARD_OPTION);
        } else {
            $shard = null;
        }
        if (!is_string($shard) || !preg_match('/^\d+\/\d+$/', $shard)) {
            throw new Invalid_Option('The [--shard] option must be in the format "index/total".');
        }
        [$index, $total] = explode('/', $shard);
        if (!is_numeric($index) || !is_numeric($total)) {
            throw new Invalid_Option('The [--shard] option must be in the format "index/total".');
        }
        if ($index <= 0 || $total <= 0 || $index > $total) {
            throw new Invalid_Option('The [--shard] option index must be a non-negative integer less than the total number of shards.');
        }
        $index = (int) $index;
        $total = (int) $total;
        return ['index' => $index, 'total' => $total];
    }
}