<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City\Subscriber;

use Php_Unit\Event\Test\Considered_Risky;
use Php_Unit\Event\Test\Considered_Risky_Subscriber;
/**
 * @internal
 */
final class Test_Considered_Risky_Subscriber extends Subscriber implements Considered_Risky_Subscriber
{
    public function notify(Considered_Risky $event): void
    {
        $this->logger()->test_considered_risky($event);
    }
}