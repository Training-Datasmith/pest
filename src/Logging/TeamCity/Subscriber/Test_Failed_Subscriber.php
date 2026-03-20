<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City\Subscriber;

use Php_Unit\Event\Test\Failed;
use Php_Unit\Event\Test\Failed_Subscriber;
/**
 * @internal
 */
final class Test_Failed_Subscriber extends Subscriber implements Failed_Subscriber
{
    public function notify(Failed $event): void
    {
        $this->logger()->test_failed($event);
    }
}