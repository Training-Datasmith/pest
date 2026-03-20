<?php

declare (strict_types=1);
namespace Pest\Bootstrappers;

use Pest\Contracts\Bootstrapper;
use Pest\Support\View;
use Symfony\Component\Console\Output\Output_Interface;
/**
 * @internal
 */
final readonly class Boot_View implements Bootstrapper
{
    /**
     * Creates a new instance of the Boot View.
     */
    public function __construct(private Output_Interface $output)
    {
        // ..
    }
    /**
     * Boots the view renderer.
     */
    public function boot(): void
    {
        View::render_using($this->output);
    }
}