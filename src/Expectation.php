<?php

declare (strict_types=1);
namespace Pest;

use Attribute;
use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use OutOfRangeException;
use Pest\Arch\Contracts\Arch_Expectation;
use Pest\Arch\Expectations\Targeted;
use Pest\Arch\Expectations\To_Be_Used_In;
use Pest\Arch\Expectations\To_Be_Used_In_Nothing;
use Pest\Arch\Expectations\To_Only_Be_Used_In;
use Pest\Arch\Expectations\To_Only_Use;
use Pest\Arch\Expectations\To_Use;
use Pest\Arch\Expectations\To_Use_Nothing;
use Pest\Arch\Pending_Arch_Expectation;
use Pest\Arch\Support\File_Line_Finder;
use Pest\Concerns\Extendable;
use Pest\Concerns\Pipeable;
use Pest\Concerns\Retrievable;
use Pest\Exceptions\Expectation_Not_Found;
use Pest\Exceptions\Invalid_Expectation;
use Pest\Exceptions\Invalid_Expectation_Value;
use Pest\Expectations\Each_Expectation;
use Pest\Expectations\Higher_Order_Expectation;
use Pest\Expectations\Opposite_Expectation;
use Pest\Matchers\Any;
use Pest\Support\Expectation_Pipeline;
use Pest\Support\Reflection;
use Php_Unit\Architecture\Elements\Object_Description;
use Php_Unit\Framework\Expectation_Failed_Exception;
use Reflection_Enum;
use ReflectionMethod;
use ReflectionProperty;
/**
 * @template TValue
 *
 * @property OppositeExpectation $not Creates the opposite expectation.
 * @property EachExpectation $each Creates an expectation on each element on the traversable value.
 * @property PendingArchExpectation $classes
 * @property PendingArchExpectation $traits
 * @property PendingArchExpectation $interfaces
 * @property PendingArchExpectation $enums
 *
 * @mixin Mixins\Expectation<TValue>
 * @mixin PendingArchExpectation
 */
final class Expectation
{
    /** @use Extendable<self<TValue>> */
    use Extendable;
    use Pipeable;
    use Retrievable;
    /**
     * Creates a new expectation.
     *
     * @param  TValue  $value
     */
    public function __construct(public mixed $value)
    {
        // ..
    }
    /**
     * Creates a new expectation.
     *
     * @template TAndValue
     *
     * @param  TAndValue  $value
     * @return self<TAndValue>
     */
    public function and(mixed $value): Expectation
    {
        return $value instanceof self ? $value : new self($value);
    }
    /**
     * Creates a new expectation with the decoded JSON value.
     *
     * @return self<array<int|string, mixed>|bool>
     */
    public function json(): Expectation
    {
        if (!is_string($this->value)) {
            Invalid_Expectation_Value::expected('string');
        }
        $this->to_be_json();
        /** @var array<int|string, mixed>|bool $value */
        $value = json_decode($this->value, true, 512, JSON_THROW_ON_ERROR);
        return $this->and($value);
    }
    /**
     * Dump the expectation value.
     *
     * @return self<TValue>
     */
    public function dump(mixed ...$arguments): self
    {
        if (function_exists('dump')) {
            dump($this->value, ...$arguments);
        } else {
            var_dump($this->value);
        }
        return $this;
    }
    /**
     * Dump the expectation value and end the script.
     *
     * @return never
     */
    public function dd(mixed ...$arguments): void
    {
        if (function_exists('dd')) {
            dd($this->value, ...$arguments);
        }
        var_dump($this->value);
        exit(1);
    }
    /**
     * Dump the expectation value when the result of the condition is truthy.
     *
     * @param  (Closure(TValue): bool)|bool  $condition
     * @return self<TValue>
     */
    public function dd_when(Closure|bool $condition, mixed ...$arguments): Expectation
    {
        $condition = $condition instanceof Closure ? $condition($this->value) : $condition;
        if (!$condition) {
            return $this;
        }
        $this->dd(...$arguments);
    }
    /**
     * Dump the expectation value when the result of the condition is falsy.
     *
     * @param  (Closure(TValue): bool)|bool  $condition
     * @return self<TValue>
     */
    public function dd_unless(Closure|bool $condition, mixed ...$arguments): Expectation
    {
        $condition = $condition instanceof Closure ? $condition($this->value) : $condition;
        if ($condition) {
            return $this;
        }
        $this->dd(...$arguments);
    }
    /**
     * Send the expectation value to Ray along with all given arguments.
     *
     * @return self<TValue>
     */
    public function ray(mixed ...$arguments): self
    {
        if (function_exists('ray')) {
            ray($this->value, ...$arguments);
        }
        return $this;
    }
    /**
     * Creates the opposite expectation for the value.
     *
     * @return OppositeExpectation<TValue>
     */
    public function not(): Opposite_Expectation
    {
        return new Opposite_Expectation($this);
    }
    /**
     * Creates an expectation on each item of the iterable "value".
     *
     * @return EachExpectation<TValue>
     */
    public function each(?callable $callback = null): Each_Expectation
    {
        if (!is_iterable($this->value)) {
            throw new BadMethodCallException('Expectation value is not iterable.');
        }
        if (is_callable($callback)) {
            foreach ($this->value as $key => $item) {
                $callback(new self($item), $key);
            }
        }
        return new Each_Expectation($this);
    }
    /**
     * Allows you to specify a sequential set of expectations for each item in a iterable "value".
     *
     * @template TSequenceValue
     *
     * @param  (callable(self<TValue>, self<string|int>): void)|TSequenceValue  ...$callbacks
     * @return self<TValue>
     */
    public function sequence(mixed ...$callbacks): self
    {
        if (!is_iterable($this->value)) {
            throw new BadMethodCallException('Expectation value is not iterable.');
        }
        if ($callbacks === []) {
            throw new InvalidArgumentException('No sequence expectations defined.');
        }
        $index = $values_count = 0;
        foreach ($this->value as $key => $value) {
            $values_count++;
            if ($callbacks[$index] instanceof Closure) {
                $callbacks[$index](new self($value), new self($key));
            } else {
                (new self($value))->to_equal($callbacks[$index]);
            }
            $index = isset($callbacks[$index + 1]) ? $index + 1 : 0;
        }
        if ($values_count < count($callbacks)) {
            throw new OutOfRangeException('Sequence expectations are more than the iterable items.');
        }
        return $this;
    }
    /**
     * If the subject matches one of the given "expressions", the expression callback will run.
     *
     * @template TMatchSubject of array-key
     *
     * @param  (callable(): TMatchSubject)|TMatchSubject  $subject
     * @param  array<TMatchSubject, (callable(self<TValue>): mixed)|TValue>  $expressions
     * @return self<TValue>
     */
    public function match(mixed $subject, array $expressions): self
    {
        $subject = $subject instanceof Closure ? $subject() : $subject;
        $matched = false;
        foreach ($expressions as $key => $callback) {
            if ($subject != $key) {
                // @pest-arch-ignore-line
                continue;
            }
            $matched = true;
            if (is_callable($callback)) {
                $callback(new self($this->value));
                continue;
            }
            $this->and($this->value)->to_equal($callback);
            break;
        }
        if ($matched === false) {
            throw new Expectation_Failed_Exception('Unhandled match value.');
        }
        return $this;
    }
    /**
     * Apply the callback if the given "condition" is falsy.
     *
     * @param  (callable(): bool)|bool  $condition
     * @param  callable(Expectation<TValue>): mixed  $callback
     * @return self<TValue>
     */
    public function unless(callable|bool $condition, callable $callback): Expectation
    {
        $condition = is_callable($condition) ? $condition : static fn(): bool => $condition;
        return $this->when(!$condition(), $callback);
    }
    /**
     * Apply the callback if the given "condition" is truthy.
     *
     * @param  (callable(): bool)|bool  $condition
     * @param  callable(self<TValue>): mixed  $callback
     * @return self<TValue>
     */
    public function when(callable|bool $condition, callable $callback): self
    {
        $condition = is_callable($condition) ? $condition : static fn(): bool => $condition;
        if ($condition()) {
            $callback($this->and($this->value));
        }
        return $this;
    }
    /**
     * Dynamically calls methods on the class or creates a new higher order expectation.
     *
     * @param  array<int, mixed>  $parameters
     * @return Expectation<TValue>|HigherOrderExpectation<Expectation<TValue>, TValue>
     */
    public function __call(string $method, array $parameters): Expectation|Higher_Order_Expectation|Pending_Arch_Expectation|Arch_Expectation
    {
        if (!self::has_method($method)) {
            if (!is_object($this->value) && method_exists(Pending_Arch_Expectation::class, $method)) {
                $pending_arch_expectation = new Pending_Arch_Expectation($this, []);
                return $pending_arch_expectation->{$method}(...$parameters);
                // @phpstan-ignore-line
            }
            if (!is_object($this->value)) {
                throw new BadMethodCallException(sprintf('Method "%s" does not exist in %s.', $method, gettype($this->value)));
            }
            /* @phpstan-ignore-next-line */
            return new Higher_Order_Expectation($this, call_user_func_array($this->value->{$method}(...), $parameters));
        }
        $closure = $this->get_expectation_closure($method);
        $reflection_closure = new \ReflectionFunction($closure);
        $expectation = $reflection_closure->get_closure_this();
        if ($reflection_closure->get_return_type()?->__toString() === Arch_Expectation::class) {
            return $closure(...$parameters);
        }
        assert(is_object($expectation));
        Expectation_Pipeline::for($closure)->send(...$parameters)->through($this->pipes($method, $expectation, Expectation::class))->run();
        return $this;
    }
    /**
     * Creates a new expectation closure from the given name.
     *
     * @throws ExpectationNotFound
     */
    private function get_expectation_closure(string $name): Closure
    {
        if (method_exists(Mixins\Expectation::class, $name)) {
            // @phpstan-ignore-next-line
            return Closure::from_callable([new Mixins\Expectation($this->value), $name]);
        }
        if (self::has_extend($name)) {
            $extend = self::$extends[$name]->bind_to($this, Expectation::class);
            if ($extend != false) {
                // @pest-arch-ignore-line
                return $extend;
            }
        }
        throw Expectation_Not_Found::from_name($name);
    }
    /**
     * Dynamically calls methods on the class without any arguments or creates a new higher order expectation.
     *
     * @return Expectation<TValue>|OppositeExpectation<TValue>|EachExpectation<TValue>|HigherOrderExpectation<Expectation<TValue>, TValue|null>|TValue
     */
    public function __get(string $name): mixed
    {
        if (!self::has_method($name)) {
            if (!is_object($this->value) && method_exists(Pending_Arch_Expectation::class, $name)) {
                /* @phpstan-ignore-next-line */
                return $this->{$name}();
            }
            /* @phpstan-ignore-next-line */
            return new Higher_Order_Expectation($this, $this->retrieve($name, $this->value));
        }
        /* @phpstan-ignore-next-line */
        return $this->{$name}();
    }
    /**
     * Checks if the given expectation method exists.
     */
    public static function has_method(string $name): bool
    {
        return method_exists(self::class, $name) || method_exists(Mixins\Expectation::class, $name) || self::has_extend($name);
    }
    /**
     * Matches any value.
     */
    public function any(): Any
    {
        return new Any();
    }
    /**
     * Asserts that the given expectation target use the given dependencies.
     *
     * @param  array<int, string>|string  $targets
     */
    public function to_use(array|string $targets): Arch_Expectation
    {
        return To_Use::make($this, $targets);
    }
    /**
     * Asserts that the given expectation target does have the given permissions
     */
    public function to_have_file_system_permissions(string $permissions): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => substr(sprintf('%o', fileperms($object->path)), -4) === $permissions, sprintf('permissions to be [%s]', $permissions), File_Line_Finder::where(fn(string $line): bool => str_contains($line, '<?php')));
    }
    /**
     * Asserts that the given expectation target to have line count less than the given number.
     */
    public function to_have_line_count_less_than(int $lines): Arch_Expectation
    {
        return Targeted::make(
            $this,
            fn(Object_Description $object): bool => count(file($object->path)) < $lines,
            // @phpstan-ignore-line
            sprintf('to have less than %d lines of code', $lines),
            File_Line_Finder::where(fn(string $line): bool => str_contains($line, '<?php'))
        );
    }
    /**
     * Asserts that the given expectation target have all methods documented.
     */
    public function to_have_methods_documented(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) === false || array_filter(Reflection::get_methods_from_reflection_class($object->reflection_class), fn(ReflectionMethod $method): bool => (enum_exists($object->name) === false || in_array($method->name, ['from', 'tryFrom', 'cases'], true) === false) && realpath($method->get_file_name() ?: '/') === realpath($object->path) && $method->get_doc_comment() === false) === [], 'to have methods with documentation / annotations', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target have all properties documented.
     */
    public function to_have_properties_documented(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) === false || array_filter(Reflection::get_properties_from_reflection_class($object->reflection_class), fn(ReflectionProperty $property): bool => (enum_exists($object->name) === false || in_array($property->name, ['value', 'name'], true) === false) && realpath($property->get_declaring_class()->get_file_name() ?: '/') === realpath($object->path) && $property->is_promoted() === false && $property->get_doc_comment() === false) === [], 'to have properties with documentation / annotations', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target use the "declare(strict_types=1)" declaration.
     */
    public function to_use_strict_types(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => (bool) preg_match('/^<\?php\s*(\/\*[\s\S]*?\*\/|\/\/[^\r\n]*(?:\r?\n|$)|\s)*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/m', (string) file_get_contents($object->path)), 'to use strict types', File_Line_Finder::where(fn(string $line): bool => str_contains($line, '<?php')));
    }
    /**
     * Asserts that the given expectation target uses strict equality.
     */
    public function to_use_strict_equality(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => !str_contains((string) file_get_contents($object->path), ' == ') && !str_contains((string) file_get_contents($object->path), ' != '), 'to use strict equality', File_Line_Finder::where(fn(string $line): bool => str_contains($line, ' == ') || str_contains($line, ' != ')));
    }
    /**
     * Asserts that the given expectation target is final.
     */
    public function to_be_final(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => !enum_exists($object->name) && isset($object->reflection_class) && $object->reflection_class->is_final(), 'to be final', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target is readonly.
     */
    public function to_be_readonly(): Arch_Expectation
    {
        return Targeted::make(
            $this,
            fn(Object_Description $object): bool => !enum_exists($object->name) && isset($object->reflection_class) && $object->reflection_class->is_read_only() && assert(true),
            // @phpstan-ignore-line
            'to be readonly',
            File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class'))
        );
    }
    /**
     * Asserts that the given expectation target is trait.
     */
    public function to_be_trait(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) && $object->reflection_class->is_trait(), 'to be trait', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation targets are traits.
     */
    public function to_be_traits(): Arch_Expectation
    {
        return $this->to_be_trait();
    }
    /**
     * Asserts that the given expectation target is abstract.
     */
    public function to_be_abstract(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) && $object->reflection_class->is_abstract(), 'to be abstract', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target has a specific method.
     *
     * @param  array<int, string>|string  $method
     */
    public function to_have_method(array|string $method): Arch_Expectation
    {
        $methods = is_array($method) ? $method : [$method];
        return Targeted::make($this, fn(Object_Description $object): bool => count(array_filter($methods, fn(string $method): bool => isset($object->reflection_class) && $object->reflection_class->has_method($method))) === count($methods), sprintf("to have method '%s'", implode("', '", $methods)), File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target has a specific methods.
     *
     * @param  array<int, string>  $methods
     */
    public function to_have_methods(array $methods): Arch_Expectation
    {
        return $this->to_have_method($methods);
    }
    /**
     * Not supported.
     */
    public function to_have_public_methods_besides(): never
    {
        throw Invalid_Expectation::from_methods(['toHavePublicMethodsBesides']);
    }
    /**
     * Not supported.
     */
    public function to_have_public_methods(): never
    {
        throw Invalid_Expectation::from_methods(['toHavePublicMethods']);
    }
    /**
     * Not supported.
     */
    public function to_have_protected_methods_besides(): never
    {
        throw Invalid_Expectation::from_methods(['toHaveProtectedMethodsBesides']);
    }
    /**
     * Not supported.
     */
    public function to_have_protected_methods(): never
    {
        throw Invalid_Expectation::from_methods(['toHaveProtectedMethods']);
    }
    /**
     * Not supported.
     */
    public function to_have_private_methods_besides(): never
    {
        throw Invalid_Expectation::from_methods(['toHavePrivateMethodsBesides']);
    }
    /**
     * Not supported.
     */
    public function to_have_private_methods(): never
    {
        throw Invalid_Expectation::from_methods(['toHavePrivateMethods']);
    }
    /**
     * Asserts that the given expectation target is enum.
     */
    public function to_be_enum(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) && $object->reflection_class->is_enum(), 'to be enum', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation targets are enums.
     */
    public function to_be_enums(): Arch_Expectation
    {
        return $this->to_be_enum();
    }
    /**
     * Asserts that the given expectation target is a class.
     */
    public function to_be_class(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => class_exists($object->name) && !enum_exists($object->name), 'to be class', File_Line_Finder::where(fn(string $line): bool => true));
    }
    /**
     * Asserts that the given expectation targets are classes.
     */
    public function to_be_classes(): Arch_Expectation
    {
        return $this->to_be_class();
    }
    /**
     * Asserts that the given expectation target is interface.
     */
    public function to_be_interface(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) && $object->reflection_class->is_interface(), 'to be interface', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation targets are interfaces.
     */
    public function to_be_interfaces(): Arch_Expectation
    {
        return $this->to_be_interface();
    }
    /**
     * Asserts that the given expectation target to be subclass of the given class.
     */
    public function to_extend(string $class): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) && ($class === $object->reflection_class->get_name() || $object->reflection_class->is_subclass_of($class)), sprintf("to extend '%s'", $class), File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target to be have a parent class.
     */
    public function to_extend_nothing(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => $object->reflection_class->get_parent_class() === false, 'to extend nothing', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target to use the given trait.
     */
    public function to_use_trait(string $trait): Arch_Expectation
    {
        return $this->to_use_traits($trait);
    }
    /**
     * Asserts that the given expectation target to use the given traits.
     *
     * @param  array<int, string>|string  $traits
     */
    public function to_use_traits(array|string $traits): Arch_Expectation
    {
        $traits = is_array($traits) ? $traits : [$traits];
        return Targeted::make($this, function (Object_Description $object) use ($traits): bool {
            foreach ($traits as $trait) {
                if (isset($object->reflection_class) === false) {
                    return false;
                }
                if (!in_array($trait, $object->reflection_class->get_trait_names(), true)) {
                    return false;
                }
            }
            return true;
        }, "to use traits '" . implode("', '", $traits) . "'", File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target to not implement any interfaces.
     */
    public function to_implement_nothing(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) && $object->reflection_class->get_interface_names() === [], 'to implement nothing', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target to only implement the given interfaces.
     *
     * @param  array<int, string>|string  $interfaces
     */
    public function to_only_implement(array|string $interfaces): Arch_Expectation
    {
        $interfaces = is_array($interfaces) ? $interfaces : [$interfaces];
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) && count($interfaces) === count($object->reflection_class->get_interface_names()) && array_diff($interfaces, $object->reflection_class->get_interface_names()) === [], "to only implement '" . implode("', '", $interfaces) . "'", File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target to have the given prefix.
     */
    public function to_have_prefix(string $prefix): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) && str_starts_with((string) $object->reflection_class->get_short_name(), $prefix), "to have prefix '{$prefix}'", File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target to have the given suffix.
     */
    public function to_have_suffix(string $suffix): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) && str_ends_with((string) $object->reflection_class->get_name(), $suffix), "to have suffix '{$suffix}'", File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target to implement the given interfaces.
     *
     * @param  array<int, string>|string  $interfaces
     */
    public function to_implement(array|string $interfaces): Arch_Expectation
    {
        $interfaces = is_array($interfaces) ? $interfaces : [$interfaces];
        return Targeted::make($this, function (Object_Description $object) use ($interfaces): bool {
            foreach ($interfaces as $interface) {
                if (!isset($object->reflection_class) || !$object->reflection_class->implements_interface($interface)) {
                    return false;
                }
            }
            return true;
        }, "to implement '" . implode("', '", $interfaces) . "'", File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target "only" use on the given dependencies.
     *
     * @param  array<int, string>|string  $targets
     */
    public function to_only_use(array|string $targets): Arch_Expectation
    {
        return To_Only_Use::make($this, $targets);
    }
    /**
     * Asserts that the given expectation target does not use any dependencies.
     */
    public function to_use_nothing(): Arch_Expectation
    {
        return To_Use_Nothing::make($this);
    }
    /**
     * Asserts that the source code of the given expectation target does not include suspicious characters.
     */
    public function to_have_suspicious_characters(): Arch_Expectation
    {
        throw Invalid_Expectation::from_methods(['toHaveSuspiciousCharacters']);
    }
    /**
     * Not supported.
     */
    public function to_be_used(): never
    {
        throw Invalid_Expectation::from_methods(['toBeUsed']);
    }
    /**
     * Asserts that the given expectation dependency is used by the given targets.
     *
     * @param  array<int, string>|string  $targets
     */
    public function to_be_used_in(array|string $targets): Arch_Expectation
    {
        return To_Be_Used_In::make($this, $targets);
    }
    /**
     * Asserts that the given expectation dependency is "only" used by the given targets.
     *
     * @param  array<int, string>|string  $targets
     */
    public function to_only_be_used_in(array|string $targets): Arch_Expectation
    {
        return To_Only_Be_Used_In::make($this, $targets);
    }
    /**
     * Asserts that the given expectation dependency is not used.
     */
    public function to_be_used_in_nothing(): Arch_Expectation
    {
        return To_Be_Used_In_Nothing::make($this);
    }
    /**
     * Asserts that the given expectation dependency is an invokable class.
     */
    public function to_be_invokable(): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) && $object->reflection_class->has_method('__invoke'), 'to be invokable', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation is iterable and contains snake_case keys.
     *
     * @return self<TValue>
     */
    public function to_have_snake_case_keys(string $message = ''): self
    {
        if (!is_iterable($this->value)) {
            Invalid_Expectation_Value::expected('iterable');
        }
        foreach ($this->value as $k => $item) {
            if (is_string($k)) {
                $this->and($k)->to_be_snake_case($message);
            }
            if (is_array($item)) {
                $this->and($item)->to_have_snake_case_keys($message);
            }
        }
        return $this;
    }
    /**
     * Asserts that the given expectation is iterable and contains kebab-case keys.
     *
     * @return self<TValue>
     */
    public function to_have_kebab_case_keys(string $message = ''): self
    {
        if (!is_iterable($this->value)) {
            Invalid_Expectation_Value::expected('iterable');
        }
        foreach ($this->value as $k => $item) {
            if (is_string($k)) {
                $this->and($k)->to_be_kebab_case($message);
            }
            if (is_array($item)) {
                $this->and($item)->to_have_kebab_case_keys($message);
            }
        }
        return $this;
    }
    /**
     * Asserts that the given expectation is iterable and contains camelCase keys.
     *
     * @return self<TValue>
     */
    public function to_have_camel_case_keys(string $message = ''): self
    {
        if (!is_iterable($this->value)) {
            Invalid_Expectation_Value::expected('iterable');
        }
        foreach ($this->value as $k => $item) {
            if (is_string($k)) {
                $this->and($k)->to_be_camel_case($message);
            }
            if (is_array($item)) {
                $this->and($item)->to_have_camel_case_keys($message);
            }
        }
        return $this;
    }
    /**
     * Asserts that the given expectation is iterable and contains StudlyCase keys.
     *
     * @return self<TValue>
     */
    public function to_have_studly_case_keys(string $message = ''): self
    {
        if (!is_iterable($this->value)) {
            Invalid_Expectation_Value::expected('iterable');
        }
        foreach ($this->value as $k => $item) {
            if (is_string($k)) {
                $this->and($k)->to_be_studly_case($message);
            }
            if (is_array($item)) {
                $this->and($item)->to_have_studly_case_keys($message);
            }
        }
        return $this;
    }
    /**
     * Asserts that the given expectation target to have the given attribute.
     */
    public function to_have_attribute(string $attribute): Arch_Expectation
    {
        return Targeted::make($this, fn(Object_Description $object): bool => isset($object->reflection_class) && $object->reflection_class->get_attributes($attribute) !== [], "to have attribute '{$attribute}'", File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target has a constructor method.
     */
    public function to_have_constructor(): Arch_Expectation
    {
        return $this->to_have_method('__construct');
    }
    /**
     * Asserts that the given expectation target has a destructor method.
     */
    public function to_have_destructor(): Arch_Expectation
    {
        return $this->to_have_method('__destruct');
    }
    /**
     * Asserts that the given expectation target is a backed enum of given type.
     */
    private function to_be_backed_enum(string $backing_type): Arch_Expectation
    {
        return Targeted::make(
            $this,
            fn(Object_Description $object): bool => isset($object->reflection_class) && $object->reflection_class->is_enum() && (new Reflection_Enum($object->name))->is_backed() && (string) (new Reflection_Enum($object->name))->get_backing_type() === $backing_type,
            // @phpstan-ignore-line
            'to be ' . $backing_type . ' backed enum',
            File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class'))
        );
    }
    /**
     * Asserts that the given expectation targets are string backed enums.
     */
    public function to_be_string_backed_enums(): Arch_Expectation
    {
        return $this->to_be_string_backed_enum();
    }
    /**
     * Asserts that the given expectation targets are int backed enums.
     */
    public function to_be_int_backed_enums(): Arch_Expectation
    {
        return $this->to_be_int_backed_enum();
    }
    /**
     * Asserts that the given expectation target is a string backed enum.
     */
    public function to_be_string_backed_enum(): Arch_Expectation
    {
        return $this->to_be_backed_enum('string');
    }
    /**
     * Asserts that the given expectation target is an int backed enum.
     */
    public function to_be_int_backed_enum(): Arch_Expectation
    {
        return $this->to_be_backed_enum('int');
    }
}