<?php

declare (strict_types=1);
namespace Pest\Plugins\Parallel\Paratest;

use function assert;
use function fclose;
use function feof;
use function fopen;
use function fread;
use function fseek;
use function ftell;
use function fwrite;
use Para_Test\Options;
use Pest\Plugins\Parallel\Support\Compact_Printer;
use Pest\Support\State_Generator;
use Php_Unit\Test_Runner\Test_Result\Test_Result;
use Php_Unit\Text_Ui\Output\Printer;
use Sebastian_Bergmann\Timer\Duration;
use Spl_File_Info;
use function strlen;
use Symfony\Component\Console\Formatter\Output_Formatter;
use Symfony\Component\Console\Output\Output_Interface;
/**
 * @internal
 */
final class Result_Printer
{
    /**
     * If the test should be marked as todo.
     */
    public bool $last_was_todo = false;
    /**
     * The "native" printer.
     */
    public readonly Printer $printer;
    /**
     * The state.
     */
    public int $passed_tests = 0;
    /**
     * The "compact" printer.
     */
    private readonly Compact_Printer $compact_printer;
    /** @var resource|null */
    private $teamcity_log_file_handle;
    /** @var array<non-empty-string, int> */
    private array $tail_positions;
    public function __construct(private readonly Output_Interface $output, private readonly Options $options)
    {
        $this->printer = new readonly class($this->output) implements Printer
        {
            public function __construct(private Output_Interface $output)
            {
            }
            public function print(string $buffer): void
            {
                $buffer = Output_Formatter::escape($buffer);
                if (str_starts_with($buffer, "\nGenerating code coverage report")) {
                    return;
                }
                if (str_starts_with($buffer, 'done [')) {
                    return;
                }
                $this->output->write(Output_Formatter::escape($buffer));
            }
            public function flush(): void
            {
            }
        };
        $this->compact_printer = Compact_Printer::default();
        if (!$this->options->configuration->has_logfile_teamcity()) {
            return;
        }
        $teamcity_log_file_handle = fopen($this->options->configuration->logfile_teamcity(), 'ab+');
        assert($teamcity_log_file_handle !== false);
        $this->teamcity_log_file_handle = $teamcity_log_file_handle;
    }
    /** @param  list<SplFileInfo>  $teamcityFiles */
    public function print_feedback(Spl_File_Info $progress_file, Spl_File_Info $output_file, array $teamcity_files): void
    {
        if ($this->options->needs_teamcity) {
            $teamcity_progress = $this->tail_multiple($teamcity_files);
            if ($this->teamcity_log_file_handle !== null) {
                fwrite($this->teamcity_log_file_handle, $teamcity_progress);
            }
        }
        if ($this->options->configuration->output_is_team_city()) {
            assert(isset($teamcity_progress));
            $this->output->write($teamcity_progress);
            return;
        }
        if ($this->options->configuration->no_progress()) {
            return;
        }
        $unexpected_output = $this->tail($output_file);
        if ($unexpected_output !== '') {
            if (preg_match('/^T+$/', $unexpected_output) > 0) {
                return;
            }
            $this->output->write($unexpected_output);
        }
        $feedback_items = $this->tail($progress_file);
        if ($feedback_items === '') {
            return;
        }
        $feedback_items = (string) preg_replace('/ +\d+ \/ \d+ \( ?\d+%\)\s*/', '', $feedback_items);
        $actual_test_count = strlen($feedback_items);
        for ($index = 0; $index < $actual_test_count; $index++) {
            $this->print_feedback_item($feedback_items[$index]);
        }
    }
    /**
     * @param  list<SplFileInfo>  $teamcityFiles
     * @param  list<SplFileInfo>  $testdoxFiles
     */
    public function print_results(Test_Result $test_result, array $teamcity_files, array $testdox_files, Duration $duration): void
    {
        if ($this->options->needs_teamcity) {
            $teamcity_progress = $this->tail_multiple($teamcity_files);
            if ($this->teamcity_log_file_handle !== null) {
                fwrite($this->teamcity_log_file_handle, $teamcity_progress);
                $resource = $this->teamcity_log_file_handle;
                $this->teamcity_log_file_handle = null;
                fclose($resource);
            }
        }
        if ($this->options->configuration->output_is_team_city()) {
            assert(isset($teamcity_progress));
            $this->output->write($teamcity_progress);
            return;
        }
        if ($this->options->configuration->output_is_test_dox()) {
            $this->output->write($this->tail_multiple($testdox_files));
            return;
        }
        $state = (new State_Generator())->from_php_unit_test_result($this->passed_tests, $test_result);
        $this->compact_printer->errors($state);
        $this->compact_printer->recap($state, $test_result, $duration, $this->options);
    }
    private function print_feedback_item(string $item): void
    {
        if ($this->last_was_todo) {
            $this->last_was_todo = false;
            return;
        }
        if ($item === 'T') {
            $this->last_was_todo = true;
        }
        if ($item === '.') {
            $this->passed_tests++;
        }
        $this->compact_printer->description_item($item);
    }
    /** @param  list<SplFileInfo>  $files */
    private function tail_multiple(array $files): string
    {
        $content = '';
        foreach ($files as $file) {
            if (!$file->is_file()) {
                continue;
            }
            $content .= $this->tail($file);
        }
        return $content;
    }
    private function tail(Spl_File_Info $file): string
    {
        $path = $file->get_pathname();
        assert($path !== '');
        $handle = fopen($path, 'r');
        assert($handle !== false);
        $fseek = fseek($handle, $this->tail_positions[$path] ?? 0);
        assert($fseek === 0);
        $contents = '';
        while (!feof($handle)) {
            $fread = fread($handle, 8192);
            assert($fread !== false);
            $contents .= $fread;
        }
        $ftell = ftell($handle);
        assert($ftell !== false);
        $this->tail_positions[$path] = $ftell;
        fclose($handle);
        return $contents;
    }
}