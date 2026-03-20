<?php

declare (strict_types=1);
namespace Pest\Logging;

use Nuno_Maduro\Collision\Adapters\Phpunit\State;
use Pest\Exceptions\Should_Not_Happen;
use Pest\Support\State_Generator;
use Pest\Support\Str;
use Php_Unit\Event\Code\Test;
use Php_Unit\Event\Code\Test_Method;
use Php_Unit\Event\Code\Throwable;
use Php_Unit\Event\Test\After_Last_Test_Method_Errored;
use Php_Unit\Event\Test\Before_First_Test_Method_Errored;
use Php_Unit\Event\Test\Considered_Risky;
use Php_Unit\Event\Test\Errored;
use Php_Unit\Event\Test\Failed;
use Php_Unit\Event\Test\Marked_Incomplete;
use Php_Unit\Event\Test\Skipped;
use Php_Unit\Event\Test_Suite\Test_Suite;
use Php_Unit\Event\Test_Suite\Test_Suite_For_Test_Method_With_Data_Provider;
use Php_Unit\Framework\Exception as FrameworkException;
use Php_Unit\Test_Runner\Test_Result\Test_Result as PhpUnitTestResult;
/**
 * @internal
 */
final readonly class Converter
{
    /**
     * The prefix for the test suite name.
     */
    private const string PREFIX = 'P\\';
    /**
     *  The state generator.
     */
    private State_Generator $state_generator;
    /**
     * Creates a new instance of the Converter.
     */
    public function __construct(private string $root_path)
    {
        $this->state_generator = new State_Generator();
    }
    /**
     * Gets the test case method name.
     */
    public function get_test_case_method_name(Test $test): string
    {
        if (!$test instanceof Test_Method) {
            throw Should_Not_Happen::from_message('Not an instance of TestMethod');
        }
        return $test->test_dox()->prettified_method_name();
    }
    /**
     * Gets the test case location.
     */
    public function get_test_case_location(Test $test): string
    {
        if (!$test instanceof Test_Method) {
            throw Should_Not_Happen::from_message('Not an instance of TestMethod');
        }
        $path = $test->test_dox()->prettified_class_name();
        $relative_path = $this->to_relative_path($path);
        // TODO: Get the description without the dataset.
        $description = $test->test_dox()->prettified_method_name();
        return "{$relative_path}::{$description}";
    }
    /**
     * Gets the exception message.
     */
    public function get_exception_message(Throwable $throwable): string
    {
        if (is_a($throwable->class_name(), Framework_Exception::class, true)) {
            return $throwable->message();
        }
        $buffer = $throwable->class_name();
        $throwable_message = $throwable->message();
        if ($throwable_message !== '') {
            $buffer .= ": {$throwable_message}";
        }
        return $buffer;
    }
    /**
     * Gets the exception details.
     */
    public function get_exception_details(Throwable $throwable): string
    {
        $buffer = $this->get_stack_trace($throwable);
        while ($throwable->has_previous()) {
            $throwable = $throwable->previous();
            $buffer .= sprintf("\nCaused by\n%s\n%s", $throwable->description(), $this->get_stack_trace($throwable));
        }
        return $buffer;
    }
    /**
     * Gets the stack trace.
     */
    public function get_stack_trace(Throwable $throwable): string
    {
        $stack_trace = $throwable->stack_trace();
        // Split stacktrace per frame.
        $frames = explode("\n", $stack_trace);
        // Remove empty lines
        $frames = array_filter($frames);
        // clean the paths of each frame.
        $frames = array_map($this->to_relative_path(...), $frames);
        // Format stacktrace as `at <path>`
        $frames = array_map(fn(string $frame): string => "at {$frame}", $frames);
        return implode("\n", $frames);
    }
    /**
     * Gets the test suite name.
     */
    public function get_test_suite_name(Test_Suite $test_suite): string
    {
        if ($test_suite instanceof Test_Suite_For_Test_Method_With_Data_Provider) {
            $first_test = $this->get_first_test($test_suite);
            if ($first_test instanceof Test_Method) {
                return $this->get_test_method_name_without_dataset_suffix($first_test);
            }
        }
        $name = $test_suite->name();
        if (!str_starts_with($name, self::PREFIX)) {
            return $name;
        }
        return Str::after($name, self::PREFIX);
    }
    /**
     * Gets the trimmed test class name.
     */
    public function get_trimmed_test_class_name(Test_Method $test): string
    {
        return Str::after($test->class_name(), self::PREFIX);
    }
    /**
     * Gets the test suite location.
     */
    public function get_test_suite_location(Test_Suite $test_suite): ?string
    {
        $first_test = $this->get_first_test($test_suite);
        if (!$first_test instanceof Test_Method) {
            return null;
        }
        $path = $first_test->test_dox()->prettified_class_name();
        $class_relative_path = $this->to_relative_path($path);
        if ($test_suite instanceof Test_Suite_For_Test_Method_With_Data_Provider) {
            $method_name = $this->get_test_method_name_without_dataset_suffix($first_test);
            return "{$class_relative_path}::{$method_name}";
        }
        return $class_relative_path;
    }
    /**
     * Gets the prettified test method name without dataset-related suffix.
     */
    private function get_test_method_name_without_dataset_suffix(Test_Method $test_method): string
    {
        return Str::before_last($test_method->test_dox()->prettified_method_name(), ' with data set ');
    }
    /**
     * Gets the first test from the test suite.
     */
    private function get_first_test(Test_Suite $test_suite): ?Test_Method
    {
        $tests = $test_suite->tests()->as_array();
        // TODO: figure out how to get the file path without a test being there.
        if ($tests === []) {
            return null;
        }
        $first_test = $tests[0];
        if (!$first_test instanceof Test_Method) {
            throw Should_Not_Happen::from_message('Not an instance of TestMethod');
        }
        return $first_test;
    }
    /**
     * Gets the test suite size.
     */
    public function get_test_suite_size(Test_Suite $test_suite): int
    {
        return $test_suite->count();
    }
    /**
     * Transforms the given path in relative path.
     */
    private function to_relative_path(string $path): string
    {
        // Remove cwd from the path.
        return str_replace("{$this->root_path}" . DIRECTORY_SEPARATOR, '', $path);
    }
    /**
     * Get the test result.
     */
    public function get_state_from_result(Php_Unit_Test_Result $result): State
    {
        $events = [...$result->test_errored_events(), ...$result->test_failed_events(), ...$result->test_skipped_events(), ...array_merge(...array_values($result->test_considered_risky_events())), ...$result->test_marked_incomplete_events()];
        $number_of_not_passed_tests = count(array_unique(array_map(function (After_Last_Test_Method_Errored|Before_First_Test_Method_Errored|Errored|Failed|Skipped|Considered_Risky|Marked_Incomplete $event): string {
            if ($event instanceof Before_First_Test_Method_Errored || $event instanceof After_Last_Test_Method_Errored) {
                return $event->test_class_name();
            }
            return $this->get_test_case_location($event->test());
        }, $events)));
        $number_of_passed_tests = $result->number_of_tests_run() - $number_of_not_passed_tests;
        return $this->state_generator->from_php_unit_test_result($number_of_passed_tests, $result);
    }
}