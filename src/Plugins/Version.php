<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Support\View;
use function Pest\version;
/**
 * @internal
 */
final class Version implements Handles_Arguments
{
    use Concerns\Handle_Arguments;
    /**
     * {@inheritDoc}
     */
    public function handle_arguments(array $arguments): array
    {
        if ($this->has_argument('--version', $arguments)) {
            View::render('version', ['version' => version()]);
            exit(0);
        }
        return $arguments;
    }
}