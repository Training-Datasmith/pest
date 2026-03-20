<?php

declare (strict_types=1);
namespace Pest\Pending_Calls;

use Closure;
use Pest\Exceptions\After_Before_Test_Function;
use Pest\Pending_Calls\Concerns\Describable;
use Pest\Support\Arr;
use Pest\Support\Backtrace;
use Pest\Support\Chainable_Closure;
use Pest\Support\Higher_Order_Message_Collection;
use Pest\Support\Null_Closure;
use Pest\Test_Suite;
/**
 * @internal
 *
 * @mixin TestCall
 */
final class Before_Each_Call
{
    use Describable;
    /**
     * Holds the before each closure.
     */
    private readonly Closure $closure;
    /**
     * The test call proxies.
     */
    private readonly Higher_Order_Message_Collection $test_call_proxies;
    /**
     * The test case proxies.
     */
    private readonly Higher_Order_Message_Collection $test_case_proxies;
    /**
     * Creates a new Pending Call.
     */
    public function __construct(public readonly Test_Suite $test_suite, private readonly string $filename, ?Closure $closure = null)
    {
        $this->closure = $closure instanceof Closure ? $closure : Null_Closure::create();
        $this->test_call_proxies = new Higher_Order_Message_Collection();
        $this->test_case_proxies = new Higher_Order_Message_Collection();
        $this->describing = Describe_Call::describing();
    }
    /**
     * Creates the Call.
     */
    public function __destruct()
    {
        $describing = $this->describing;
        $test_case_proxies = $this->test_case_proxies;
        $before_each_test_call = function (Test_Call $test_call) use ($describing): void {
            if ($this->describing !== []) {
                if (Arr::last($describing) !== Arr::last($this->describing)) {
                    return;
                }
                if (!in_array(Arr::last($describing), $test_call->describing, true)) {
                    return;
                }
            }
            $this->test_call_proxies->chain($test_call);
        };
        $before_each_test_case = Chainable_Closure::bound_when(fn(): bool => $describing === [] || in_array(Arr::last($describing), $this->__describing, true), Chainable_Closure::bound(fn() => $test_case_proxies->chain($this), $this->closure)->bind_to($this, self::class))->bind_to($this, self::class);
        assert($before_each_test_case instanceof Closure);
        $this->test_suite->before_each->set($this->filename, $this, $before_each_test_call, $before_each_test_case);
    }
    /**
     * Runs the given closure after the test.
     */
    public function after(Closure $closure): self
    {
        if ($this->describing === []) {
            throw new After_Before_Test_Function($this->filename);
        }
        return $this->__call('after', [$closure]);
    }
    /**
     * Saves the calls to be used on the target.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): self
    {
        if (method_exists(Test_Call::class, $name)) {
            $this->test_call_proxies->add(Backtrace::file(), Backtrace::line(), $name, $arguments);
            return $this;
        }
        $this->test_case_proxies->add(Backtrace::file(), Backtrace::line(), $name, $arguments);
        return $this;
    }
}