<?php

declare (strict_types=1);
namespace Pest\Subscribers;

use Pest\Support\Container;
use Php_Unit\Event\Test_Runner\Configured;
use Php_Unit\Event\Test_Runner\Configured_Subscriber;
use Php_Unit\Text_Ui\Configuration\Configuration;
/**
 * @internal
 */
final class Ensure_Configuration_Is_Available implements Configured_Subscriber
{
    /**
     * Runs the subscriber.
     */
    public function notify(Configured $event): void
    {
        Container::get_instance()->add(Configuration::class, $event->configuration());
    }
}