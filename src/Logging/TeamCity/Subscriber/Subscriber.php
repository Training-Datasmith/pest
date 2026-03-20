<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City\Subscriber;

use Pest\Logging\Team_City\Team_City_Logger;
/**
 * @internal
 */
abstract class Subscriber
{
    /**
     * Creates a new Subscriber instance.
     */
    public function __construct(private readonly Team_City_Logger $logger)
    {
    }
    /**
     * Creates a new TeamCityLogger instance.
     */
    final protected function logger(): Team_City_Logger
    {
        return $this->logger;
    }
}