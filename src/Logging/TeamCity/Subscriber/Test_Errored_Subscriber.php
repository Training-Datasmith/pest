<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City\Subscriber;

use Php_Unit\Event\Test\Errored;
use Php_Unit\Event\Test\Errored_Subscriber;
/**
 * @internal
 */
final class Test_Errored_Subscriber extends Subscriber implements Errored_Subscriber
{
    public function notify(Errored $event): void
    {
        $this->logger()->test_errored($event);
    }
}