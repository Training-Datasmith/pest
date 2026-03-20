<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City\Subscriber;

use Php_Unit\Event\Test_Suite\Started;
use Php_Unit\Event\Test_Suite\Started_Subscriber;
/**
 * @internal
 */
final class Test_Suite_Started_Subscriber extends Subscriber implements Started_Subscriber
{
    public function notify(Started $event): void
    {
        $this->logger()->test_suite_started($event);
    }
}