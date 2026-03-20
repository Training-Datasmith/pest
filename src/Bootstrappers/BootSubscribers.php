<?php

declare (strict_types=1);
namespace Pest\Bootstrappers;

use Pest\Contracts\Bootstrapper;
use Pest\Subscribers;
use Pest\Support\Container;
use Php_Unit\Event;
use Php_Unit\Event\Subscriber;
/**
 * @internal
 */
final readonly class Boot_Subscribers implements Bootstrapper
{
    /**
     * The list of Subscribers.
     *
     * @var array<int, class-string<Subscriber>>
     */
    private const array SUBSCRIBERS = [Subscribers\Ensure_Configuration_Is_Available::class, Subscribers\Ensure_Ignorable_Test_Cases_Are_Ignored::class, Subscribers\Ensure_Kernel_Dump_Is_Flushed::class, Subscribers\Ensure_Team_City_Enabled::class];
    /**
     * Creates a new instance of the Boot Subscribers.
     */
    public function __construct(private Container $container)
    {
    }
    /**
     * Boots the list of Subscribers.
     */
    public function boot(): void
    {
        foreach (self::SUBSCRIBERS as $subscriber) {
            $instance = $this->container->get($subscriber);
            assert($instance instanceof Subscriber);
            Event\Facade::instance()->register_subscriber($instance);
        }
    }
}