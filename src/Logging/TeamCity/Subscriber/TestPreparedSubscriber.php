<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City\Subscriber;

use Php_Unit\Event\Test\Prepared;
use Php_Unit\Event\Test\Prepared_Subscriber;
/**
 * @internal
 */
final class Test_Prepared_Subscriber extends Subscriber implements Prepared_Subscriber
{
    public function notify(Prepared $event): void
    {
        $this->logger()->test_prepared($event);
    }
}