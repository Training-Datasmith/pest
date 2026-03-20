<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City;

use Nuno_Maduro\Collision\Adapters\Phpunit\Style;
use Pest\Exceptions\Should_Not_Happen;
use Pest\Logging\Converter;
use Pest\Logging\Team_City\Subscriber\Test_Considered_Risky_Subscriber;
use Pest\Logging\Team_City\Subscriber\Test_Errored_Subscriber;
use Pest\Logging\Team_City\Subscriber\Test_Execution_Finished_Subscriber;
use Pest\Logging\Team_City\Subscriber\Test_Failed_Subscriber;
use Pest\Logging\Team_City\Subscriber\Test_Finished_Subscriber;
use Pest\Logging\Team_City\Subscriber\Test_Prepared_Subscriber;
use Pest\Logging\Team_City\Subscriber\Test_Skipped_Subscriber;
use Pest\Logging\Team_City\Subscriber\Test_Suite_Finished_Subscriber;
use Pest\Logging\Team_City\Subscriber\Test_Suite_Started_Subscriber;
use Php_Unit\Event\Code\Test;
use Php_Unit\Event\Event_Facade_Is_Sealed_Exception;
use Php_Unit\Event\Facade;
use Php_Unit\Event\Telemetry\Duration;
use Php_Unit\Event\Telemetry\Hr_Time;
use Php_Unit\Event\Telemetry\Info;
use Php_Unit\Event\Telemetry\Snapshot;
use Php_Unit\Event\Test\Considered_Risky;
use Php_Unit\Event\Test\Errored;
use Php_Unit\Event\Test\Failed;
use Php_Unit\Event\Test\Finished;
use Php_Unit\Event\Test\Prepared;
use Php_Unit\Event\Test\Skipped;
use Php_Unit\Event\Test_Runner\Execution_Finished;
use Php_Unit\Event\Test_Suite\Finished as TestSuiteFinished;
use Php_Unit\Event\Test_Suite\Started as TestSuiteStarted;
use Php_Unit\Event\Unknown_Subscriber_Type_Exception;
use Php_Unit\Test_Runner\Test_Result\Facade as TestResultFacade;
use ReflectionClass;
use Symfony\Component\Console\Output\Console_Output;
use Symfony\Component\Console\Output\Output_Interface;
/**
 * @internal
 */
final class Team_City_Logger
{
    /**
     * The current time.
     */
    private ?Hr_Time $time = null;
    /**
     * Indicates if the summary test count has been printed.
     */
    private bool $is_summary_test_count_printed = false;
    /**
     * @var array<string, bool>
     */
    private array $test_events = [];
    /**
     * @throws EventFacadeIsSealedException
     * @throws UnknownSubscriberTypeException
     */
    public function __construct(private readonly Output_Interface $output, private readonly Converter $converter, private readonly ?int $flow_id, private readonly bool $without_duration)
    {
        $this->register_subscribers();
        $this->set_flow_id();
    }
    public function test_suite_started(Test_Suite_Started $event): void
    {
        $message = Service_Message::test_suite_started($this->converter->get_test_suite_name($event->test_suite()), $this->converter->get_test_suite_location($event->test_suite()));
        $this->output($message);
        if (!$this->is_summary_test_count_printed) {
            $this->is_summary_test_count_printed = true;
            $message = Service_Message::test_suite_count($this->converter->get_test_suite_size($event->test_suite()));
            $this->output($message);
        }
    }
    public function test_suite_finished(Test_Suite_Finished $event): void
    {
        $message = Service_Message::test_suite_finished($this->converter->get_test_suite_name($event->test_suite()));
        $this->output($message);
    }
    public function test_prepared(Prepared $event): void
    {
        $message = Service_Message::test_started($this->converter->get_test_case_method_name($event->test()), $this->converter->get_test_case_location($event->test()));
        $this->output($message);
        $this->time = $event->telemetry_info()->time();
    }
    public function test_marked_incomplete(): never
    {
        throw Should_Not_Happen::from_message('testMarkedIncomplete not implemented.');
    }
    public function test_skipped(Skipped $event): void
    {
        $this->when_first_event_for_test($event->test(), function () use ($event): void {
            $message = Service_Message::test_ignored($this->converter->get_test_case_method_name($event->test()), 'This test was ignored.');
            $this->output($message);
        });
    }
    /**
     * This will trigger in the following scenarios
     * - When an exception is thrown
     */
    public function test_errored(Errored $event): void
    {
        $this->when_first_event_for_test($event->test(), function () use ($event): void {
            $test_name = $this->converter->get_test_case_method_name($event->test());
            $message = $this->converter->get_exception_message($event->throwable());
            $details = $this->converter->get_exception_details($event->throwable());
            $message = Service_Message::test_failed($test_name, $message, $details);
            $this->output($message);
        });
    }
    /**
     * This will trigger in the following scenarios
     * - When an assertion fails
     */
    public function test_failed(Failed $event): void
    {
        $this->when_first_event_for_test($event->test(), function () use ($event): void {
            $test_name = $this->converter->get_test_case_method_name($event->test());
            $message = $this->converter->get_exception_message($event->throwable());
            $details = $this->converter->get_exception_details($event->throwable());
            if ($event->has_comparison_failure()) {
                $comparison = $event->comparison_failure();
                $message = Service_Message::comparison_failure($test_name, $message, $details, $comparison->actual(), $comparison->expected());
            } else {
                $message = Service_Message::test_failed($test_name, $message, $details);
            }
            $this->output($message);
        });
    }
    /**
     * This will trigger in the following scenarios
     * - When no assertions in a test
     */
    public function test_considered_risky(Considered_Risky $event): void
    {
        $this->when_first_event_for_test($event->test(), function () use ($event): void {
            $message = Service_Message::test_ignored($this->converter->get_test_case_method_name($event->test()), $event->message());
            $this->output($message);
        });
    }
    public function test_finished(Finished $event): void
    {
        if (!$this->time instanceof Hr_Time) {
            throw Should_Not_Happen::from_message('Start time has not been set.');
        }
        $test_name = $this->converter->get_test_case_method_name($event->test());
        $duration = $event->telemetry_info()->time()->duration($this->time)->as_float();
        if ($this->without_duration) {
            $duration = 100;
        }
        $message = Service_Message::test_finished($test_name, (int) ($duration * 1000));
        $this->output($message);
    }
    public function test_execution_finished(Execution_Finished $event): void
    {
        $result = Test_Result_Facade::result();
        $state = $this->converter->get_state_from_result($result);
        assert($this->output instanceof Console_Output);
        $style = new Style($this->output);
        $telemetry = $event->telemetry_info();
        if ($this->without_duration) {
            $reflector = new ReflectionClass($telemetry);
            $property = $reflector->get_property('current');
            $snapshot = $property->get_value($telemetry);
            assert($snapshot instanceof Snapshot);
            $telemetry = new Info($snapshot, Duration::from_seconds_and_nanoseconds(1, 0), $telemetry->memory_usage_since_start(), $telemetry->duration_since_previous(), $telemetry->memory_usage_since_previous());
        }
        $style->write_recap($state, $telemetry, $result);
    }
    public function output(Service_Message $message): void
    {
        $this->output->writeln("{$message->to_string()}");
    }
    /**
     * @throws EventFacadeIsSealedException
     * @throws UnknownSubscriberTypeException
     */
    private function register_subscribers(): void
    {
        $subscribers = [new Test_Suite_Started_Subscriber($this), new Test_Suite_Finished_Subscriber($this), new Test_Prepared_Subscriber($this), new Test_Finished_Subscriber($this), new Test_Errored_Subscriber($this), new Test_Failed_Subscriber($this), new Test_Skipped_Subscriber($this), new Test_Considered_Risky_Subscriber($this), new Test_Execution_Finished_Subscriber($this)];
        Facade::instance()->register_subscribers(...$subscribers);
    }
    private function set_flow_id(): void
    {
        if ($this->flow_id === null) {
            return;
        }
        Service_Message::set_flow_id($this->flow_id);
    }
    private function when_first_event_for_test(Test $test, callable $callback): void
    {
        $test_identifier = $this->converter->get_test_case_location($test);
        if (!isset($this->test_events[$test_identifier])) {
            $this->test_events[$test_identifier] = true;
            $callback();
        }
    }
}