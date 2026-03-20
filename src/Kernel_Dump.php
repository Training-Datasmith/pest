<?php

declare (strict_types=1);
namespace Pest;

use Pest\Support\View;
use Symfony\Component\Console\Output\Output_Interface;
final class Kernel_Dump
{
    /**
     * The output buffer, if any.
     */
    private string $buffer = '';
    /**
     * Creates a new Kernel Dump instance.
     */
    public function __construct(private readonly Output_Interface $output)
    {
        // ...
    }
    /**
     * Enable the output buffering.
     */
    public function enable(): void
    {
        ob_start(function (string $message): string {
            $this->buffer .= $message;
            return '';
        });
    }
    /**
     * Disable the output buffering.
     */
    public function disable(): void
    {
        @ob_clean();
        if ($this->buffer !== '') {
            $this->flush();
        }
    }
    /**
     * Terminate the output buffering.
     */
    public function terminate(): void
    {
        $this->disable();
    }
    /**
     * Flushes the buffer.
     */
    private function flush(): void
    {
        View::render_using($this->output);
        if ($this->is_opening_headline($this->buffer)) {
            $this->buffer = implode(PHP_EOL, array_slice(explode(PHP_EOL, $this->buffer), 2));
        }
        $type = 'INFO';
        if ($this->is_internal_error($this->buffer)) {
            $type = 'ERROR';
            $this->buffer = str_replace(sprintf('An error occurred inside PHPUnit.%s%sMessage:  ', PHP_EOL, PHP_EOL), '', $this->buffer);
        }
        $this->buffer = trim($this->buffer);
        $this->buffer = rtrim($this->buffer, '.') . '.';
        $lines = explode(PHP_EOL, $this->buffer);
        $lines = array_reverse($lines);
        $first_line = array_pop($lines);
        $lines = array_reverse($lines);
        View::render('components.badge', ['type' => $type, 'content' => $first_line]);
        $this->output->writeln($lines);
        $this->buffer = '';
    }
    /**
     * Checks if the given output contains an opening headline.
     */
    private function is_opening_headline(string $output): bool
    {
        return str_contains($output, 'by Sebastian Bergmann and contributors.');
    }
    /**
     * Checks if the given output contains an opening headline.
     */
    private function is_internal_error(string $output): bool
    {
        return str_contains($output, 'An error occurred inside PHPUnit.') || str_contains($output, 'Fatal error');
    }
}