<?php

declare (strict_types=1);
namespace Pest\Plugins\Parallel\Paratest;

use function array_merge;
use function array_merge_recursive;
use function array_shift;
use function assert;
use function count;
use const DIRECTORY_SEPARATOR;
use function dirname;
use function file_get_contents;
use function max;
use Nuno_Maduro\Collision\Adapters\Phpunit\Support\Result_Reflection;
use Para_Test\Coverage\Coverage_Merger;
use Para_Test\J_Unit\Log_Merger;
use Para_Test\J_Unit\Writer;
use Para_Test\Options;
use Para_Test\Runner_Interface;
use Para_Test\Wrapper_Runner\Suite_Loader;
use Para_Test\Wrapper_Runner\Wrapper_Worker;
use Pest\Result;
use Pest\Test_Suite;
use Php_Unit\Event\Facade as EventFacade;
use Php_Unit\Event\Test\After_Last_Test_Method_Failed;
use Php_Unit\Event\Test_Runner\Warning_Triggered;
use Php_Unit\Runner\Code_Coverage;
use Php_Unit\Runner\Result_Cache\Default_Result_Cache;
use Php_Unit\Test_Runner\Test_Result\Facade as TestResultFacade;
use Php_Unit\Test_Runner\Test_Result\Test_Result;
use Php_Unit\Text_Ui\Configuration\Code_Coverage_Filter_Registry;
use Php_Unit\Util\Exclude_List;
use function realpath;
use Sebastian_Bergmann\Timer\Timer;
use Spl_File_Info;
use Symfony\Component\Console\Output\Output_Interface;
use Symfony\Component\Process\Php_Executable_Finder;
use function unlink;
use function unserialize;
use function usleep;
/**
 * @internal
 */
final class Wrapper_Runner implements Runner_Interface
{
    /**
     * The time to sleep between cycles.
     */
    private const int CYCLE_SLEEP = 10000;
    /**
     * The result printer.
     */
    private readonly Result_Printer $printer;
    /**
     * The timer.
     */
    private readonly Timer $timer;
    /** @var list<non-empty-string> */
    private array $pending = [];
    /**
     * The exit code.
     */
    private int $exitcode = -1;
    /** @var array<positive-int,WrapperWorker> */
    private array $workers = [];
    /** @var array<int,int> */
    private array $batches = [];
    /** @var list<SplFileInfo> */
    private array $unexpected_output_files = [];
    /** @var list<SplFileInfo> */
    private array $result_cache_files = [];
    /** @var list<SplFileInfo> */
    private array $test_result_files = [];
    /** @var list<SplFileInfo> */
    private array $coverage_files = [];
    /** @var list<SplFileInfo> */
    private array $junit_files = [];
    /** @var list<SplFileInfo> */
    private array $teamcity_files = [];
    /** @var list<SplFileInfo> */
    private array $testdox_files = [];
    /** @var non-empty-string[] */
    private readonly array $parameters;
    /**
     * The code coverage filter registry.
     */
    private readonly Code_Coverage_Filter_Registry $code_coverage_filter_registry;
    public function __construct(private readonly Options $options, private readonly Output_Interface $output)
    {
        $this->printer = new Result_Printer($output, $options);
        $this->timer = new Timer();
        $wrapper = realpath(dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'worker.php');
        assert($wrapper !== false);
        $php_finder = new Php_Executable_Finder();
        $php_bin = $php_finder->find(false);
        assert($php_bin !== false);
        $parameters = [$php_bin];
        $parameters = array_merge($parameters, $php_finder->find_arguments());
        if ($options->passthru_php !== null) {
            $parameters = array_merge($parameters, $options->passthru_php);
        }
        /** @var array<int, non-empty-string> $parameters */
        $parameters = $this->handle_laravel_herd($parameters);
        $parameters[] = $wrapper;
        $parameters[] = '--test-directory=' . Test_Suite::get_instance()->test_path;
        $this->parameters = $parameters;
        $this->code_coverage_filter_registry = new Code_Coverage_Filter_Registry();
    }
    public function run(): int
    {
        $directory = dirname(__DIR__);
        assert($directory !== '');
        Exclude_List::add_directory($directory);
        Test_Result_Facade::init();
        Event_Facade::instance()->seal();
        $suite_loader = new Suite_Loader($this->options, $this->output, $this->code_coverage_filter_registry);
        $this->pending = $this->get_test_files($suite_loader);
        $result = Test_Result_Facade::result();
        $this->timer->start();
        $this->start_workers();
        $this->assign_all_pending_tests();
        $this->wait_for_all_to_finish();
        return $this->complete($result);
    }
    /**
     * Handles Laravel Herd's debug and coverage modes.
     *
     * @param  array<string>  $parameters
     * @return array<string>
     */
    private function handle_laravel_herd(array $parameters): array
    {
        if (isset($_ENV['HERD_DEBUG_INI'])) {
            return array_merge($parameters, ['-c', $_ENV['HERD_DEBUG_INI']]);
        }
        return $parameters;
    }
    private function start_workers(): void
    {
        for ($token = 1; $token <= $this->options->processes; $token++) {
            $this->start_worker($token);
        }
    }
    private function assign_all_pending_tests(): void
    {
        $batch_size = $this->options->max_batch_size;
        while (count($this->pending) > 0 && count($this->workers) > 0) {
            foreach ($this->workers as $token => $worker) {
                if (!$worker->is_running()) {
                    throw $worker->get_worker_crashed_exception();
                }
                if (!$worker->is_free()) {
                    continue;
                }
                $this->flush_worker($worker);
                if ($batch_size !== 0 && $this->batches[$token] === $batch_size) {
                    $this->destroy_worker($token);
                    $worker = $this->start_worker($token);
                }
                if ($this->exitcode > 0 && $this->options->configuration->stop_on_failure()) {
                    $this->pending = [];
                } elseif (($pending = array_shift($this->pending)) !== null) {
                    $worker->assign($pending);
                    $this->batches[$token]++;
                }
            }
            usleep(self::CYCLE_SLEEP);
        }
    }
    private function flush_worker(Wrapper_Worker $worker): void
    {
        $this->exitcode = max($this->exitcode, $worker->get_exit_code());
        $this->printer->print_feedback($worker->progress_file, $worker->unexpected_output_file, $this->teamcity_files);
        $worker->reset();
    }
    private function wait_for_all_to_finish(): void
    {
        $stopped = [];
        while (count($this->workers) > 0) {
            foreach ($this->workers as $index => $worker) {
                if ($worker->is_running()) {
                    if (!isset($stopped[$index]) && $worker->is_free()) {
                        $worker->stop();
                        $stopped[$index] = true;
                    }
                    continue;
                }
                if (!$worker->is_free()) {
                    throw $worker->get_worker_crashed_exception();
                }
                $this->flush_worker($worker);
                unset($this->workers[$index]);
            }
            usleep(self::CYCLE_SLEEP);
        }
    }
    /** @param  positive-int  $token */
    private function start_worker(int $token): Wrapper_Worker
    {
        $worker = new Wrapper_Worker($this->output, $this->options, $this->parameters, $token);
        $worker->start();
        $this->batches[$token] = 0;
        $this->unexpected_output_files[] = $worker->unexpected_output_file;
        $this->unexpected_output_files[] = $worker->unexpected_output_file;
        $this->test_result_files[] = $worker->test_result_file;
        if (isset($worker->junit_file)) {
            $this->junit_files[] = $worker->junit_file;
        }
        if (isset($worker->coverage_file)) {
            $this->coverage_files[] = $worker->coverage_file;
        }
        if (isset($worker->teamcity_file)) {
            $this->teamcity_files[] = $worker->teamcity_file;
        }
        if (isset($worker->testdox_file)) {
            $this->testdox_files[] = $worker->testdox_file;
        }
        return $this->workers[$token] = $worker;
    }
    private function destroy_worker(int $token): void
    {
        $this->workers[$token]->stop();
        // We need to wait for ApplicationForWrapperWorker::end to end
        while ($this->workers[$token]->is_running()) {
            usleep(self::CYCLE_SLEEP);
        }
        unset($this->workers[$token]);
    }
    private function complete(Test_Result $test_result_sum): int
    {
        foreach ($this->test_result_files as $test_result_file) {
            if (!$test_result_file->is_file()) {
                continue;
            }
            $contents = file_get_contents($test_result_file->get_pathname());
            assert($contents !== false);
            $test_result = unserialize($contents);
            assert($test_result instanceof Test_Result);
            /** @var list<AfterLastTestMethodFailed> $failedEvents */
            $failed_events = array_merge_recursive($test_result_sum->test_failed_events(), $test_result->test_failed_events());
            $test_result_sum = new Test_Result(
                (int) $test_result_sum->has_tests() + (int) $test_result->has_tests(),
                $test_result_sum->number_of_tests_run() + $test_result->number_of_tests_run(),
                $test_result_sum->number_of_assertions() + $test_result->number_of_assertions(),
                array_merge_recursive($test_result_sum->test_errored_events(), $test_result->test_errored_events()),
                $failed_events,
                array_merge_recursive($test_result_sum->test_considered_risky_events(), $test_result->test_considered_risky_events()),
                array_merge_recursive($test_result_sum->test_suite_skipped_events(), $test_result->test_suite_skipped_events()),
                array_merge_recursive($test_result_sum->test_skipped_events(), $test_result->test_skipped_events()),
                array_merge_recursive($test_result_sum->test_marked_incomplete_events(), $test_result->test_marked_incomplete_events()),
                array_merge_recursive($test_result_sum->test_triggered_phpunit_deprecation_events(), $test_result->test_triggered_phpunit_deprecation_events()),
                array_merge_recursive($test_result_sum->test_triggered_phpunit_error_events(), $test_result->test_triggered_phpunit_error_events()),
                array_merge_recursive($test_result_sum->test_triggered_phpunit_notice_events(), $test_result->test_triggered_phpunit_notice_events()),
                array_merge_recursive($test_result_sum->test_triggered_phpunit_warning_events(), $test_result->test_triggered_phpunit_warning_events()),
                // @phpstan-ignore-next-line
                array_merge_recursive($test_result_sum->test_runner_triggered_deprecation_events(), $test_result->test_runner_triggered_deprecation_events()),
                // @phpstan-ignore-next-line
                array_merge_recursive($test_result_sum->test_runner_triggered_notice_events(), $test_result->test_runner_triggered_notice_events()),
                // @phpstan-ignore-next-line
                array_merge_recursive($test_result_sum->test_runner_triggered_warning_events(), $test_result->test_runner_triggered_warning_events()),
                // @phpstan-ignore-next-line
                array_merge_recursive($test_result_sum->errors(), $test_result->errors()),
                // @phpstan-ignore-next-line
                array_merge_recursive($test_result_sum->deprecations(), $test_result->deprecations()),
                // @phpstan-ignore-next-line
                array_merge_recursive($test_result_sum->notices(), $test_result->notices()),
                // @phpstan-ignore-next-line
                array_merge_recursive($test_result_sum->warnings(), $test_result->warnings()),
                // @phpstan-ignore-next-line
                array_merge_recursive($test_result_sum->php_deprecations(), $test_result->php_deprecations()),
                // @phpstan-ignore-next-line
                array_merge_recursive($test_result_sum->php_notices(), $test_result->php_notices()),
                // @phpstan-ignore-next-line
                array_merge_recursive($test_result_sum->php_warnings(), $test_result->php_warnings()),
                $test_result_sum->number_of_issues_ignored_by_baseline() + $test_result->number_of_issues_ignored_by_baseline()
            );
        }
        $test_result_sum = new Test_Result(Result_Reflection::number_of_tests($test_result_sum), $test_result_sum->number_of_tests_run(), $test_result_sum->number_of_assertions(), $test_result_sum->test_errored_events(), $test_result_sum->test_failed_events(), $test_result_sum->test_considered_risky_events(), $test_result_sum->test_suite_skipped_events(), $test_result_sum->test_skipped_events(), $test_result_sum->test_marked_incomplete_events(), $test_result_sum->test_triggered_phpunit_deprecation_events(), $test_result_sum->test_triggered_phpunit_error_events(), $test_result_sum->test_triggered_phpunit_notice_events(), $test_result_sum->test_triggered_phpunit_warning_events(), $test_result_sum->test_runner_triggered_deprecation_events(), $test_result_sum->test_runner_triggered_notice_events(), array_values(array_filter($test_result_sum->test_runner_triggered_warning_events(), fn(Warning_Triggered $event): bool => !str_contains($event->message(), 'No tests found'))), $test_result_sum->errors(), $test_result_sum->deprecations(), $test_result_sum->notices(), $test_result_sum->warnings(), $test_result_sum->php_deprecations(), $test_result_sum->php_notices(), $test_result_sum->php_warnings(), $test_result_sum->number_of_issues_ignored_by_baseline());
        if ($this->options->configuration->cache_result()) {
            $result_cache_sum = new Default_Result_Cache($this->options->configuration->test_result_cache_file());
            foreach ($this->result_cache_files as $result_cache_file) {
                $result_cache = new Default_Result_Cache($result_cache_file->get_pathname());
                $result_cache->load();
                $result_cache_sum->merge_with($result_cache);
            }
            $result_cache_sum->persist();
        }
        $this->printer->print_results($test_result_sum, $this->teamcity_files, $this->testdox_files, $this->timer->stop());
        $this->generate_code_coverage_reports();
        $this->generate_logs();
        $exitcode = Result::exit_code($this->options->configuration, $test_result_sum);
        $this->clear_files($this->unexpected_output_files);
        $this->clear_files($this->test_result_files);
        $this->clear_files($this->coverage_files);
        $this->clear_files($this->junit_files);
        $this->clear_files($this->teamcity_files);
        $this->clear_files($this->testdox_files);
        return $exitcode;
    }
    private function generate_code_coverage_reports(): void
    {
        if ($this->coverage_files === []) {
            return;
        }
        $coverage_manager = new Code_Coverage();
        $coverage_manager->init($this->options->configuration, $this->code_coverage_filter_registry, false);
        if (!$coverage_manager->is_active()) {
            $this->output->writeln(['', '  <fg=black;bg=yellow;options=bold> WARN </> No code coverage driver is available.</>', '']);
            return;
        }
        $coverage_merger = new Coverage_Merger($coverage_manager->code_coverage());
        foreach ($this->coverage_files as $coverage_file) {
            $coverage_merger->add_coverage_from_file($coverage_file);
        }
        $coverage_manager->generate_reports($this->printer->printer, $this->options->configuration);
    }
    private function generate_logs(): void
    {
        if ($this->junit_files === []) {
            return;
        }
        $test_suite = (new Log_Merger())->merge($this->junit_files);
        assert($test_suite instanceof \Para_Test\J_Unit\Test_Suite);
        (new Writer())->write($test_suite, $this->options->configuration->logfile_junit());
    }
    /** @param  list<SplFileInfo>  $files */
    private function clear_files(array $files): void
    {
        foreach ($files as $file) {
            if (!$file->is_file()) {
                continue;
            }
            unlink($file->get_pathname());
        }
    }
    /**
     * Returns the test files to be executed.
     *
     * @return array<int, non-empty-string>
     */
    private function get_test_files(Suite_Loader $suite_loader): array
    {
        /** @var array<string, non-empty-string> $files */
        $files = [...array_values(array_filter($suite_loader->tests, fn(string $filename): bool => !str_ends_with($filename, "eval()'d code"))), ...Test_Suite::get_instance()->tests->get_filenames()];
        return $files;
        // @phpstan-ignore-line
    }
}