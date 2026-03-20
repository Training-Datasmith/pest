<?php

declare (strict_types=1);
namespace Pest\Pending_Calls;

use Closure;
use Pest\Pending_Calls\Concerns\Describable;
use Pest\Support\Arr;
use Pest\Support\Backtrace;
use Pest\Support\Chainable_Closure;
use Pest\Support\Higher_Order_Message_Collection;
use Pest\Support\Null_Closure;
use Pest\Test_Suite;
/**
 * @internal
 */
final class After_Each_Call
{
    use Describable;
    /**
     * The "afterEach" closure.
     */
    private readonly Closure $closure;
    /**
     * The calls that should be proxied.
     */
    private readonly Higher_Order_Message_Collection $proxies;
    /**
     * Creates a new Pending Call.
     */
    public function __construct(private readonly Test_Suite $test_suite, private readonly string $filename, ?Closure $closure = null)
    {
        $this->closure = $closure instanceof Closure ? $closure : Null_Closure::create();
        $this->proxies = new Higher_Order_Message_Collection();
        $this->describing = Describe_Call::describing();
    }
    /**
     * Creates the Call.
     */
    public function __destruct()
    {
        $describing = $this->describing;
        $proxies = $this->proxies;
        $after_each_test_case = Chainable_Closure::bound_when(fn(): bool => $describing === [] || in_array(Arr::last($describing), $this->__describing, true), Chainable_Closure::bound(fn() => $proxies->chain($this), $this->closure)->bind_to($this, self::class))->bind_to($this, self::class);
        assert($after_each_test_case instanceof Closure);
        $this->test_suite->after_each->set($this->filename, $this, $after_each_test_case);
    }
    /**
     * Saves the calls to be used on the target.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): self
    {
        $this->proxies->add(Backtrace::file(), Backtrace::line(), $name, $arguments);
        return $this;
    }
}