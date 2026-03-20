<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City\Subscriber;

use Php_Unit\Event\Test_Suite\Finished;
use Php_Unit\Event\Test_Suite\Finished_Subscriber;
/**
 * @internal
 */
final class Test_Suite_Finished_Subscriber extends Subscriber implements Finished_Subscriber
{
    public function notify(Finished $event): void
    {
        $this->logger()->test_suite_finished($event);
    }
}