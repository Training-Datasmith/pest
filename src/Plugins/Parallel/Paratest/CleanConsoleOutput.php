<?php

declare (strict_types=1);
namespace Pest\Plugins\Parallel\Paratest;

use Symfony\Component\Console\Output\Console_Output;
final class Clean_Console_Output extends Console_Output
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    protected function do_write(string $message, bool $newline): void
    {
        if ($this->is_opening_headline($message)) {
            return;
        }
        parent::do_write($message, $newline);
    }
    /**
     * Removes the opening headline, witch is not needed.
     */
    private function is_opening_headline(string $message): bool
    {
        return str_contains($message, 'by Sebastian Bergmann and contributors.');
    }
}