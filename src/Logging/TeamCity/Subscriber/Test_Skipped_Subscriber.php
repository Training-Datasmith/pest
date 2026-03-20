<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City\Subscriber;

use Php_Unit\Event\Test\Skipped;
use Php_Unit\Event\Test\Skipped_Subscriber;
/**
 * @internal
 */
final class Test_Skipped_Subscriber extends Subscriber implements Skipped_Subscriber
{
    public function notify(Skipped $event): void
    {
        $this->logger()->test_skipped($event);
    }
}