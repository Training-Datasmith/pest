<?php

declare (strict_types=1);
namespace Pest\Concerns\Logging;

/**
 * @internal
 */
trait Writes_To_Console
{
    /**
     * Writes the given success message to the console.
     */
    private function write_success(string $message): void
    {
        $this->write_pest_test_output($message, 'fg-green, bold', '✓');
    }
    /**
     * Writes the given error message to the console.
     */
    private function write_error(string $message): void
    {
        $this->write_pest_test_output($message, 'fg-red, bold', '⨯');
    }
    /**
     * Writes the given warning message to the console.
     */
    private function write_warning(string $message): void
    {
        $this->write_pest_test_output($message, 'fg-yellow, bold', '-');
    }
    /**
     * Writes the give message to the console.
     */
    private function write_pest_test_output(string $message, string $color, string $symbol): void
    {
        $this->write_with_color($color, "{$symbol} ", false);
        $this->write($message);
        $this->write_new_line();
    }
}