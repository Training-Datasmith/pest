<?php

declare (strict_types=1);
namespace Pest\Expectations;

use Closure;
use Pest\Concerns\Retrievable;
use Pest\Expectation;
/**
 * @internal
 *
 * @template TOriginalValue
 * @template TValue
 *
 * @mixin Expectation<TOriginalValue>
 */
final class Higher_Order_Expectation
{
    use Retrievable;
    /**
     * @var Expectation<TValue>|EachExpectation<TValue>
     */
    private Expectation|Each_Expectation $expectation;
    /**
     * Indicates if the expectation is the opposite.
     */
    private bool $opposite = false;
    /**
     * Indicates if the expectation should reset the value.
     */
    private bool $should_reset = false;
    /**
     * Creates a new higher order expectation.
     *
     * @param  Expectation<TOriginalValue>  $original
     * @param  TValue  $value
     */
    public function __construct(private readonly Expectation $original, mixed $value)
    {
        $this->expectation = $this->expect($value);
    }
    /**
     * Creates the opposite expectation for the value.
     *
     * @return self<TOriginalValue, TValue>
     */
    public function not(): self
    {
        $this->opposite = !$this->opposite;
        return $this;
    }
    /**
     * Creates a new Expectation.
     *
     * @template TExpectValue
     *
     * @param  TExpectValue  $value
     * @return Expectation<TExpectValue>
     */
    public function expect(mixed $value): Expectation
    {
        return new Expectation($value);
    }
    /**
     * Creates a new expectation.
     *
     * @template TExpectValue
     *
     * @param  TExpectValue  $value
     * @return Expectation<TExpectValue>
     */
    public function and(mixed $value): Expectation
    {
        return $this->expect($value);
    }
    /**
     * Scope an expectation callback to the current value in
     * the HigherOrderExpectation chain.
     *
     * @param  Closure(Expectation<TValue>): void  $expectation
     * @return HigherOrderExpectation<TOriginalValue, TOriginalValue>
     */
    public function scoped(Closure $expectation): self
    {
        $expectation->__invoke($this->expectation);
        return new self($this->original, $this->original->value);
    }
    /**
     * Creates a new expectation with the decoded JSON value.
     *
     * @return self<TOriginalValue, array<string|int, mixed>|bool>
     */
    public function json(): self
    {
        return new self($this->original, $this->expectation->json()->value);
    }
    /**
     * Dynamically calls methods on the class with the given arguments.
     *
     * @param  array<int, mixed>  $arguments
     * @return self<TOriginalValue, mixed>|self<TOriginalValue, TValue>
     */
    public function __call(string $name, array $arguments): self
    {
        if (!$this->expectation_has_method($name)) {
            /* @phpstan-ignore-next-line */
            return new self($this->original, $this->get_value()->{$name}(...$arguments));
        }
        return $this->perform_assertion($name, $arguments);
    }
    /**
     * Accesses properties in the value or in the expectation.
     *
     * @return self<TOriginalValue, mixed>|self<TOriginalValue, TValue>
     */
    public function __get(string $name): self
    {
        if ($name === 'not') {
            return $this->not();
        }
        if (!$this->expectation_has_method($name)) {
            /** @var array<string, mixed>|object $value */
            $value = $this->get_value();
            return new self($this->original, $this->retrieve($name, $value));
        }
        return $this->perform_assertion($name, []);
    }
    /**
     * Determines if the original expectation has the given method name.
     */
    private function expectation_has_method(string $name): bool
    {
        if (method_exists($this->original, $name)) {
            return true;
        }
        if ($this->original::has_method($name)) {
            return true;
        }
        return $this->original::has_extend($name);
    }
    /**
     * Retrieve the applicable value based on the current reset condition.
     *
     * @return TOriginalValue|TValue
     */
    private function get_value(): mixed
    {
        return $this->should_reset ? $this->original->value : $this->expectation->value;
    }
    /**
     * Performs the given assertion with the current expectation.
     *
     * @param  array<int, mixed>  $arguments
     * @return self<TOriginalValue, TValue>
     */
    private function perform_assertion(string $name, array $arguments): self
    {
        /* @phpstan-ignore-next-line */
        $this->expectation = ($this->opposite ? $this->expectation->not() : $this->expectation)->{$name}(...$arguments);
        $this->opposite = false;
        $this->should_reset = true;
        return $this;
    }
}