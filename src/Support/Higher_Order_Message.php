<?php

declare (strict_types=1);
namespace Pest\Support;

use Closure;
use ReflectionClass;
use Throwable;
/**
 * @internal
 */
final class Higher_Order_Message
{
    public const string UNDEFINED_METHOD = 'Method %s does not exist';
    /**
     * An optional condition that will determine if the message will be executed.
     *
     * @var (Closure(): bool)|null
     */
    public ?Closure $condition = null;
    /**
     * Creates a new higher order message.
     *
     * @param  array<int, mixed>|null  $arguments
     */
    public function __construct(public string $filename, public int $line, public string $name, public ?array $arguments)
    {
        // ..
    }
    /**
     * Re-throws the given `$throwable` with the good line and filename.
     *
     * @template TValue of object
     *
     * @param  TValue  $target
     */
    public function call(object $target): mixed
    {
        if (is_callable($this->condition) && call_user_func(Closure::bind($this->condition, $target)) === false) {
            return $target;
        }
        if ($this->has_higher_order_callable()) {
            return (new Higher_Order_Callables($target))->{$this->name}(...$this->arguments);
        }
        try {
            return is_array($this->arguments) ? Reflection::call($target, $this->name, $this->arguments) : $target->{$this->name};
        } catch (Throwable $throwable) {
            Reflection::set_property_value($throwable, 'file', $this->filename);
            Reflection::set_property_value($throwable, 'line', $this->line);
            if ($throwable->get_message() === $this->get_undefined_method_message($target, $this->name)) {
                /** @var ReflectionClass<TValue> $reflection */
                $reflection = new ReflectionClass($target);
                $reflection = $reflection->get_parent_class() ?: $reflection;
                Reflection::set_property_value($throwable, 'message', sprintf('Call to undefined method %s::%s()', $reflection->get_name(), $this->name));
            }
            throw $throwable;
        }
    }
    /**
     * Indicates that this message should only be called when the given condition is true.
     *
     * @param  callable(): bool  $condition
     */
    public function when(callable $condition): self
    {
        $this->condition = Closure::from_callable($condition);
        return $this;
    }
    /**
     * Determines whether or not there exists a higher order callable with the message name.
     */
    private function has_higher_order_callable(): bool
    {
        return in_array($this->name, get_class_methods(Higher_Order_Callables::class), true);
    }
    private function get_undefined_method_message(object $target, string $method_name): string
    {
        return sprintf(self::UNDEFINED_METHOD, sprintf('%s::%s()', $target::class, $method_name));
    }
}