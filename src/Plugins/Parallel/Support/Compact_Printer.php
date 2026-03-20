<?php

declare (strict_types=1);
namespace Pest\Plugins\Parallel\Support;

use Nuno_Maduro\Collision\Adapters\Phpunit\State;
use Nuno_Maduro\Collision\Adapters\Phpunit\Style;
use Para_Test\Options;
use Php_Unit\Event\Telemetry\Garbage_Collector_Status;
use Php_Unit\Event\Telemetry\Hr_Time;
use Php_Unit\Event\Telemetry\Info;
use Php_Unit\Event\Telemetry\Memory_Usage;
use Php_Unit\Event\Telemetry\Snapshot;
use Php_Unit\Test_Runner\Test_Result\Test_Result as PHPUnitTestResult;
use Sebastian_Bergmann\Timer\Duration;
use Symfony\Component\Console\Output\Console_Output;
use Symfony\Component\Console\Output\Output_Interface;
use function Termwind\render;
use Termwind\Terminal;
use function Termwind\terminal;
/**
 * @internal
 */
final class Compact_Printer
{
    /**
     * The number of processed tests.
     */
    private int $processed = 0;
    /**
     * @var array<string, array<int, string>>
     */
    private const array LOOKUP_TABLE = ['.' => ['gray', '.'], 'S' => ['yellow', 's'], 'T' => ['cyan', 't'], 'I' => ['yellow', '!'], 'N' => ['yellow', '!'], 'D' => ['yellow', '!'], 'R' => ['yellow', '!'], 'W' => ['yellow', '!'], 'E' => ['red', '⨯'], 'F' => ['red', '⨯']];
    /**
     * Creates a new instance of the Compact Printer.
     */
    public function __construct(private readonly Terminal $terminal, private readonly Output_Interface $output, private readonly Style $style, private readonly int $compact_symbols_per_line)
    {
        // ..
    }
    /**
     * Creates a new instance of the Compact Printer.
     */
    public static function default(): self
    {
        return new self(terminal(), new Console_Output(decorated: true), new Style(new Console_Output(decorated: true)), terminal()->width() - 4);
    }
    /**
     * Output an empty line in the console. Useful for providing a little breathing room.
     */
    public function new_line(): void
    {
        render('<div class="py-1"></div>');
    }
    /**
     * Outputs the given description item from the ProgressPrinter as a gorgeous, colored symbol.
     */
    public function description_item(string $item): void
    {
        [$color, $icon] = self::LOOKUP_TABLE[$item] ?? self::LOOKUP_TABLE['.'];
        $symbols_on_current_line = $this->processed % $this->compact_symbols_per_line;
        if ($symbols_on_current_line >= $this->terminal->width() - 4) {
            $symbols_on_current_line = 0;
        }
        if ($symbols_on_current_line === 0) {
            $this->output->writeln('');
            $this->output->write('  ');
        }
        $this->output->write(sprintf('<fg=%s;options=bold>%s</>', $color, $icon));
        $this->processed++;
    }
    /**
     * Outputs all errors from the given state using Collision's beautiful error output.
     */
    public function errors(State $state): void
    {
        $this->output->writeln('');
        $this->style->write_errors_summary($state);
    }
    /**
     * Outputs a clean recap of the test run, including the number of tests, assertions, and failures.
     */
    public function recap(State $state, Php_Unit_Test_Result $test_result, Duration $duration, Options $options): void
    {
        assert($this->output instanceof Console_Output);
        $nanoseconds = $duration->as_nanoseconds() % 1000000000;
        $snapshot_duration = Hr_Time::from_seconds_and_nanoseconds((int) $duration->as_seconds(), $nanoseconds);
        $telemetry_duration = \Php_Unit\Event\Telemetry\Duration::from_seconds_and_nanoseconds((int) $duration->as_seconds(), $nanoseconds);
        $status = gc_status();
        $garbage_collector_status = new Garbage_Collector_Status($status['runs'], $status['collected'], $status['threshold'], $status['roots'], 0.0, 0.0, 0.0, 0.0, false, false, false, 0);
        $telemetry = new Info(new Snapshot($snapshot_duration, Memory_Usage::from_bytes(0), Memory_Usage::from_bytes(0), $garbage_collector_status), $telemetry_duration, Memory_Usage::from_bytes(0), \Php_Unit\Event\Telemetry\Duration::from_seconds_and_nanoseconds(0, 0), Memory_Usage::from_bytes(0));
        $this->style->write_recap($state, $telemetry, $test_result);
        $this->output->write("\x1b[1A");
        $this->output->write([sprintf('  <fg=gray>Parallel:</> <fg=default>%s process%s</>', $options->processes, $options->processes > 1 ? 'es' : ''), "\n", "\n"]);
    }
}