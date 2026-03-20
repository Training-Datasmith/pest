<?php

declare (strict_types=1);
namespace Pest\Configuration;

use Nuno_Maduro\Collision\Adapters\Phpunit\Printers\Default_Printer;
/**
 * @internal
 */
final readonly class Printer
{
    /**
     * Sets the theme to compact.
     */
    public function compact(): self
    {
        Default_Printer::compact(true);
        return $this;
    }
}