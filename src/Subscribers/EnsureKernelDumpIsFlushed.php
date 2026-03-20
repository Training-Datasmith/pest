<?php

declare (strict_types=1);
namespace Pest\Subscribers;

use Pest\Kernel_Dump;
use Pest\Support\Container;
use Php_Unit\Event\Test_Runner\Started;
use Php_Unit\Event\Test_Runner\Started_Subscriber;
/**
 * @internal
 */
final class Ensure_Kernel_Dump_Is_Flushed implements Started_Subscriber
{
    /**
     * Runs the subscriber.
     */
    public function notify(Started $event): void
    {
        $kernel_dump = Container::get_instance()->get(Kernel_Dump::class);
        assert($kernel_dump instanceof Kernel_Dump);
        $kernel_dump->disable();
    }
}