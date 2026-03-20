<?php

declare (strict_types=1);
namespace Pest\Bootstrappers;

use Pest\Contracts\Bootstrapper;
use Pest\Kernel_Dump;
use Pest\Support\Container;
use Symfony\Component\Console\Output\Output_Interface;
/**
 * @internal
 */
final readonly class Boot_Kernel_Dump implements Bootstrapper
{
    /**
     * Creates a new Boot Kernel Dump instance.
     */
    public function __construct(private Output_Interface $output)
    {
        // ...
    }
    /**
     * Boots the kernel dump.
     */
    public function boot(): void
    {
        Container::get_instance()->add(Kernel_Dump::class, $kernel_dump = new Kernel_Dump($this->output));
        $kernel_dump->enable();
    }
}