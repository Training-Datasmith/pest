<?php

declare (strict_types=1);
namespace Pest\Subscribers;

use Pest\Logging\Converter;
use Pest\Logging\Team_City\Team_City_Logger;
use Pest\Test_Suite;
use Php_Unit\Event\Test_Runner\Configured;
use Php_Unit\Event\Test_Runner\Configured_Subscriber;
use Symfony\Component\Console\Input\Input_Interface;
use Symfony\Component\Console\Output\Output_Interface;
/**
 * @internal
 */
final readonly class Ensure_Team_City_Enabled implements Configured_Subscriber
{
    /**
     * Creates a new Configured Subscriber instance.
     */
    public function __construct(private Input_Interface $input, private Output_Interface $output, private Test_Suite $test_suite)
    {
    }
    /**
     * Runs the subscriber.
     */
    public function notify(Configured $event): void
    {
        if (!$this->input->has_parameter_option('--teamcity')) {
            return;
        }
        $flow_id = getenv('FLOW_ID');
        $flow_id = is_string($flow_id) ? (int) $flow_id : getmypid();
        new Team_City_Logger($this->output, new Converter($this->test_suite->root_path), $flow_id === false ? null : $flow_id, getenv('COLLISION_IGNORE_DURATION') !== false);
    }
}