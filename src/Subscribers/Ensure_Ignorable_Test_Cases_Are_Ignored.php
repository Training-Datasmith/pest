<?php

declare (strict_types=1);
namespace Pest\Subscribers;

use Php_Unit\Event\Test_Runner\Started;
use Php_Unit\Event\Test_Runner\Started_Subscriber;
use Php_Unit\Event\Test_Runner\Warning_Triggered;
use Php_Unit\Test_Runner\Test_Result\Collector;
use Php_Unit\Test_Runner\Test_Result\Facade;
use ReflectionClass;
/**
 * @internal
 */
final class Ensure_Ignorable_Test_Cases_Are_Ignored implements Started_Subscriber
{
    /**
     * Runs the subscriber.
     */
    public function notify(Started $event): void
    {
        $reflection = new ReflectionClass(Facade::class);
        $property = $reflection->get_property('collector');
        $collector = $property->get_value();
        assert($collector instanceof Collector);
        $reflection = new ReflectionClass($collector);
        $property = $reflection->get_property('testRunnerTriggeredWarningEvents');
        /** @var array<int, WarningTriggered> $testRunnerTriggeredWarningEvents */
        $test_runner_triggered_warning_events = $property->get_value($collector);
        $test_runner_triggered_warning_events = array_values(array_filter($test_runner_triggered_warning_events, fn(Warning_Triggered $event): bool => str_contains($event->message(), 'No tests found in class') === false));
        $property->set_value($collector, $test_runner_triggered_warning_events);
    }
}