<?php

declare (strict_types=1);
namespace Pest\Support;

use Nuno_Maduro\Collision\Adapters\Phpunit\State;
use Nuno_Maduro\Collision\Adapters\Phpunit\Test_Result;
use Nuno_Maduro\Collision\Exceptions\Test_Outcome;
use Php_Unit\Event\Code\Test_Dox_Builder;
use Php_Unit\Event\Code\Test_Method;
use Php_Unit\Event\Code\Throwable_Builder;
use Php_Unit\Event\Test\Errored;
use Php_Unit\Event\Test_Data\Test_Data_Collection;
use Php_Unit\Framework\Skipped_With_Message_Exception;
use Php_Unit\Metadata\Metadata_Collection;
use Php_Unit\Test_Runner\Test_Result\Test_Result as PHPUnitTestResult;
final class State_Generator
{
    public function from_php_unit_test_result(int $passed_tests, Php_Unit_Test_Result $test_result): State
    {
        $state = new State();
        foreach ($test_result->test_errored_events() as $test_result_event) {
            if ($test_result_event instanceof Errored) {
                $state->add(Test_Result::from_pest_parallel_test_case($test_result_event->test(), Test_Result::FAIL, $test_result_event->throwable()));
            } else {
                // @phpstan-ignore-next-line
                $state->add(Test_Result::from_before_first_test_method_errored($test_result_event));
            }
        }
        foreach ($test_result->test_failed_events() as $test_result_event) {
            $state->add(Test_Result::from_pest_parallel_test_case($test_result_event->test(), Test_Result::FAIL, $test_result_event->throwable()));
        }
        foreach ($test_result->test_marked_incomplete_events() as $test_result_event) {
            $state->add(Test_Result::from_pest_parallel_test_case($test_result_event->test(), Test_Result::INCOMPLETE, $test_result_event->throwable()));
        }
        foreach ($test_result->test_considered_risky_events() as $risky_events) {
            foreach ($risky_events as $risky_event) {
                $state->add(Test_Result::from_pest_parallel_test_case($risky_event->test(), Test_Result::RISKY, Throwable_Builder::from(new Test_Outcome($risky_event->message()))));
            }
        }
        foreach ($test_result->test_skipped_events() as $test_result_event) {
            if ($test_result_event->message() === '__TODO__') {
                $state->add(Test_Result::from_pest_parallel_test_case($test_result_event->test(), Test_Result::TODO));
                continue;
            }
            $state->add(Test_Result::from_pest_parallel_test_case($test_result_event->test(), Test_Result::SKIPPED, Throwable_Builder::from(new Skipped_With_Message_Exception($test_result_event->message()))));
        }
        foreach ($test_result->deprecations() as $test_result_event) {
            foreach ($test_result_event->triggering_tests() as $triggering_test) {
                ['test' => $test] = $triggering_test;
                $state->add(Test_Result::from_pest_parallel_test_case($test, Test_Result::DEPRECATED, Throwable_Builder::from(new Test_Outcome($test_result_event->description()))));
            }
        }
        foreach ($test_result->php_deprecations() as $test_result_event) {
            foreach ($test_result_event->triggering_tests() as $triggering_test) {
                ['test' => $test] = $triggering_test;
                $state->add(Test_Result::from_pest_parallel_test_case($test, Test_Result::DEPRECATED, Throwable_Builder::from(new Test_Outcome($test_result_event->description()))));
            }
        }
        foreach ($test_result->notices() as $test_result_event) {
            foreach ($test_result_event->triggering_tests() as $triggering_test) {
                ['test' => $test] = $triggering_test;
                $state->add(Test_Result::from_pest_parallel_test_case($test, Test_Result::NOTICE, Throwable_Builder::from(new Test_Outcome($test_result_event->description()))));
            }
        }
        foreach ($test_result->php_notices() as $test_result_event) {
            foreach ($test_result_event->triggering_tests() as $triggering_test) {
                ['test' => $test] = $triggering_test;
                $state->add(Test_Result::from_pest_parallel_test_case($test, Test_Result::NOTICE, Throwable_Builder::from(new Test_Outcome($test_result_event->description()))));
            }
        }
        foreach ($test_result->warnings() as $test_result_event) {
            foreach ($test_result_event->triggering_tests() as $triggering_test) {
                ['test' => $test] = $triggering_test;
                $state->add(Test_Result::from_pest_parallel_test_case($test, Test_Result::WARN, Throwable_Builder::from(new Test_Outcome($test_result_event->description()))));
            }
        }
        foreach ($test_result->php_warnings() as $test_result_event) {
            foreach ($test_result_event->triggering_tests() as $triggering_test) {
                ['test' => $test] = $triggering_test;
                $state->add(Test_Result::from_pest_parallel_test_case($test, Test_Result::WARN, Throwable_Builder::from(new Test_Outcome($test_result_event->description()))));
            }
        }
        // for each test that passed, we need to add it to the state
        for ($i = 0; $i < $passed_tests; $i++) {
            $state->add(Test_Result::from_pest_parallel_test_case(new Test_Method(
                "{$i}",
                // @phpstan-ignore-line
                '',
                // @phpstan-ignore-line
                '',
                // @phpstan-ignore-line
                1,
                Test_Dox_Builder::from_class_name_and_method_name('', ''),
                // @phpstan-ignore-line
                Metadata_Collection::from_array([]),
                Test_Data_Collection::from_array([])
            ), Test_Result::PASS));
        }
        return $state;
    }
}