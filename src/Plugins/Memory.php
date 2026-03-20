<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Adds_Output;
use Pest\Contracts\Plugins\Handles_Arguments;
use Symfony\Component\Console\Output\Output_Interface;
/**
 * @internal
 */
final class Memory implements Adds_Output, Handles_Arguments
{
    use Concerns\Handle_Arguments;
    /**
     * If memory should be displayed.
     */
    private bool $enabled = false;
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
    public function handle_arguments(array $arguments): array
    {
        $this->enabled = $this->has_argument('--memory', $arguments);
        return $this->pop_argument('--memory', $arguments);
    }
    /**
     * {@inheritdoc}
     */
    public function add_output(int $exit_code): int
    {
        if ($this->enabled) {
            $this->output->writeln(sprintf('  <fg=gray>Memory:</>   <fg=default>%s MB</>', round(memory_get_usage(true) / 1000 ** 2, 3)));
        }
        return $exit_code;
    }
}