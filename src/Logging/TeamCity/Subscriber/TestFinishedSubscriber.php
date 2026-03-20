<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City\Subscriber;

use Php_Unit\Event\Test\Finished;
use Php_Unit\Event\Test\Finished_Subscriber;
/**
 * @internal
 */
final class Test_Finished_Subscriber extends Subscriber implements Finished_Subscriber
{
    public function notify(Finished $event): void
    {
        $this->logger()->test_finished($event);
    }
}