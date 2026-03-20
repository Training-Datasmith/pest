<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City\Subscriber;

use Php_Unit\Event\Test_Runner\Execution_Finished;
use Php_Unit\Event\Test_Runner\Execution_Finished_Subscriber;
/**
 * @internal
 */
final class Test_Execution_Finished_Subscriber extends Subscriber implements Execution_Finished_Subscriber
{
    public function notify(Execution_Finished $event): void
    {
        $this->logger()->test_execution_finished($event);
    }
}