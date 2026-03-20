<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Adds_Output;
use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Support\Str;
use Symfony\Component\Console\Input\Argv_Input;
use Symfony\Component\Console\Input\Input_Definition;
use Symfony\Component\Console\Input\Input_Option;
use Symfony\Component\Console\Output\Output_Interface;
/**
 * @internal
 */
final class Coverage implements Adds_Output, Handles_Arguments
{
    private const string COVERAGE_OPTION = 'coverage';
    private const string MIN_OPTION = 'min';
    private const string EXACTLY_OPTION = 'exactly';
    /**
     * Whether it should show the coverage or not.
     */
    public bool $coverage = false;
    /**
     * Whether it should show the coverage or not.
     */
    public bool $compact = false;
    /**
     * The minimum coverage.
     */
    public float $coverage_min = 0.0;
    /**
     * The exactly coverage.
     */
    public ?float $coverage_exactly = null;
    /**
     * Creates a new Plugin instance.
     */
    public function __construct(private readonly Output_Interface $output)
    {
        // ..
    }
    /**
     * {@inheritdoc}
     */
    public function handle_arguments(array $originals): array
    {
        $arguments = [...[''], ...array_values(array_filter($originals, function (string $original): bool {
            foreach ([self::COVERAGE_OPTION, self::MIN_OPTION, self::EXACTLY_OPTION] as $option) {
                if ($original === sprintf('--%s', $option)) {
                    return true;
                }
                if (Str::starts_with($original, sprintf('--%s=', $option))) {
                    return true;
                }
            }
            return false;
        }))];
        $originals = array_flip($originals);
        foreach ($arguments as $argument) {
            unset($originals[$argument]);
        }
        $originals = array_flip($originals);
        $inputs = [];
        $inputs[] = new Input_Option(self::COVERAGE_OPTION, null, Input_Option::VALUE_NONE);
        $inputs[] = new Input_Option(self::MIN_OPTION, null, Input_Option::VALUE_REQUIRED);
        $inputs[] = new Input_Option(self::EXACTLY_OPTION, null, Input_Option::VALUE_REQUIRED);
        $input = new Argv_Input($arguments, new Input_Definition($inputs));
        if ((bool) $input->get_option(self::COVERAGE_OPTION)) {
            $this->coverage = true;
            $originals[] = '--coverage-php';
            $originals[] = \Pest\Support\Coverage::get_path();
            if (!\Pest\Support\Coverage::is_available()) {
                if (\Pest\Support\Coverage::using_xdebug()) {
                    $this->output->writeln(['', "  <fg=default;bg=red;options=bold> ERROR </> Unable to get coverage using Xdebug. Did you set <href=https://xdebug.org/docs/code_coverage#mode>Xdebug's coverage mode</>?</>", '']);
                } else {
                    $this->output->writeln(['', '  <fg=default;bg=red;options=bold> ERROR </> No code coverage driver is available.</>', '']);
                }
                exit(1);
            }
        }
        if ($input->get_option(self::MIN_OPTION) !== null) {
            /** @var int|float $minOption */
            $min_option = $input->get_option(self::MIN_OPTION);
            $this->coverage_min = (float) $min_option;
        }
        if ($input->get_option(self::EXACTLY_OPTION) !== null) {
            /** @var int|float $exactlyOption */
            $exactly_option = $input->get_option(self::EXACTLY_OPTION);
            $this->coverage_exactly = (float) $exactly_option;
        }
        if ($_SERVER['COLLISION_PRINTER_COMPACT'] ?? false) {
            $this->compact = true;
        }
        return $originals;
    }
    /**
     * {@inheritdoc}
     */
    public function add_output(int $exit_code): int
    {
        if (Parallel::is_worker()) {
            return $exit_code;
        }
        if ($exit_code === 0 && $this->coverage) {
            if (!\Pest\Support\Coverage::is_available()) {
                $this->output->writeln("\n  <fg=white;bg=red;options=bold> ERROR </> No code coverage driver is available.</>");
                exit(1);
            }
            $coverage = \Pest\Support\Coverage::report($this->output, $this->compact);
            $exit_code = (int) ($coverage < $this->coverage_min);
            if ($exit_code === 0 && $this->coverage_exactly !== null) {
                $comparable_coverage = $this->compute_comparable_coverage($coverage);
                $comparable_coverage_exactly = $this->compute_comparable_coverage($this->coverage_exactly);
                $exit_code = $comparable_coverage === $comparable_coverage_exactly ? 0 : 1;
                if ($exit_code === 1) {
                    $this->output->writeln(sprintf("\n  <fg=white;bg=red;options=bold> FAIL </> Code coverage not exactly <fg=white;options=bold> %s %%</>, currently <fg=red;options=bold> %s %%</>.", number_format($this->coverage_exactly, 1), number_format(floor($coverage * 10) / 10, 1)));
                }
            } elseif ($exit_code === 1) {
                $this->output->writeln(sprintf("\n  <fg=white;bg=red;options=bold> FAIL </> Code coverage below expected <fg=white;options=bold> %s %%</>, currently <fg=red;options=bold> %s %%</>.", number_format($this->coverage_min, 1), number_format(floor($coverage * 10) / 10, 1)));
            }
            $this->output->writeln(['']);
        }
        return $exit_code;
    }
    /**
     * Computes the comparable coverage to a percentage with one decimal.
     */
    private function compute_comparable_coverage(float $coverage): float
    {
        return floor($coverage * 10) / 10;
    }
}