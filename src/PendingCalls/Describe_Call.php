<?php

declare (strict_types=1);
namespace Pest\Pending_Calls;

use Closure;
use Pest\Support\Backtrace;
use Pest\Support\Description;
use Pest\Test_Suite;
/**
 * @internal
 */
final class Describe_Call
{
    /**
     * The current describe call.
     *
     * @var array<int, Description>
     */
    private static array $describing = [];
    /**
     * The describe "before each" call.
     */
    private ?Before_Each_Call $current_before_each_call = null;
    /**
     * Creates a new Pending Call.
     */
    public function __construct(public readonly Test_Suite $test_suite, public readonly string $filename, public readonly Description $description, public readonly Closure $tests)
    {
    }
    /**
     * What is the current describing.
     *
     * @return array<int, Description>
     */
    public static function describing(): array
    {
        return self::$describing;
    }
    /**
     * Creates the Call.
     */
    public function __destruct()
    {
        unset($this->current_before_each_call);
        self::$describing[] = $this->description;
        try {
            ($this->tests)();
        } finally {
            array_pop(self::$describing);
        }
    }
    /**
     * Dynamically calls methods on each test call.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): self
    {
        $filename = Backtrace::file();
        if (!$this->current_before_each_call instanceof Before_Each_Call) {
            $this->current_before_each_call = new Before_Each_Call(Test_Suite::get_instance(), $filename);
            $this->current_before_each_call->describing[] = $this->description;
        }
        $this->current_before_each_call->{$name}(...$arguments);
        return $this;
    }
}