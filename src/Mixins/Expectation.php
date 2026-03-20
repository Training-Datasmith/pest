<?php

declare (strict_types=1);
namespace Pest\Mixins;

use BadMethodCallException;
use Closure;
use Countable;
use DateTimeInterface;
use Error;
use Illuminate\Testing\Test_Response;
use InvalidArgumentException;
use JsonSerializable;
use Pest\Exceptions\Invalid_Expectation_Value;
use Pest\Matchers\Any;
use Pest\Support\Arr;
use Pest\Support\Exporter;
use Pest\Support\Null_Closure;
use Pest\Support\Str;
use Pest\Test_Suite;
use Php_Unit\Framework\Assert;
use Php_Unit\Framework\Constraint\Constraint;
use Php_Unit\Framework\Expectation_Failed_Exception;
use Php_Unit\Framework\Test_Case;
use ReflectionFunction;
use ReflectionNamedType;
use Throwable;
use Traversable;
/**
 * @internal
 *
 * @template TValue
 *
 * @mixin \Pest\Expectation<TValue>
 */
final class Expectation
{
    /**
     * The exporter instance, if any.
     */
    private ?Exporter $exporter = null;
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
     * Asserts that two variables have the same type and
     * value. Used on objects, it asserts that two
     * variables reference the same object.
     *
     * @return self<TValue>
     */
    public function to_be(mixed $expected, string $message = ''): self
    {
        Assert::assert_same($expected, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is empty.
     *
     * @return self<TValue>
     */
    public function to_be_empty(string $message = ''): self
    {
        Assert::assert_empty($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is true.
     *
     * @return self<TValue>
     */
    public function to_be_true(string $message = ''): self
    {
        Assert::assert_true($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is truthy.
     *
     * @return self<TValue>
     */
    public function to_be_truthy(string $message = ''): self
    {
        Assert::assert_true((bool) $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is false.
     *
     * @return self<TValue>
     */
    public function to_be_false(string $message = ''): self
    {
        Assert::assert_false($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is falsy.
     *
     * @return self<TValue>
     */
    public function to_be_falsy(string $message = ''): self
    {
        Assert::assert_false((bool) $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is greater than $expected.
     *
     * @return self<TValue>
     */
    public function to_be_greater_than(int|float|string|DateTimeInterface $expected, string $message = ''): self
    {
        Assert::assert_greater_than($expected, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is greater than or equal to $expected.
     *
     * @return self<TValue>
     */
    public function to_be_greater_than_or_equal(int|float|string|DateTimeInterface $expected, string $message = ''): self
    {
        Assert::assert_greater_than_or_equal($expected, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is less than or equal to $expected.
     *
     * @return self<TValue>
     */
    public function to_be_less_than(int|float|string|DateTimeInterface $expected, string $message = ''): self
    {
        Assert::assert_less_than($expected, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is less than $expected.
     *
     * @return self<TValue>
     */
    public function to_be_less_than_or_equal(int|float|string|DateTimeInterface $expected, string $message = ''): self
    {
        Assert::assert_less_than_or_equal($expected, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that $needle is an element of the value.
     *
     * @return self<TValue>
     */
    public function to_contain(mixed ...$needles): self
    {
        foreach ($needles as $needle) {
            if (is_string($this->value)) {
                Assert::assert_string_contains_string((string) $needle, $this->value);
            } else {
                if (!is_iterable($this->value)) {
                    Invalid_Expectation_Value::expected('iterable');
                }
                Assert::assert_contains($needle, $this->value);
            }
        }
        return $this;
    }
    /**
     * Asserts that $needle equal an element of the value.
     *
     * @return self<TValue>
     */
    public function to_contain_equal(mixed ...$needles): self
    {
        if (!is_iterable($this->value)) {
            Invalid_Expectation_Value::expected('iterable');
        }
        foreach ($needles as $needle) {
            Assert::assert_contains_equals($needle, $this->value);
        }
        return $this;
    }
    /**
     * Asserts that the value starts with $expected.
     *
     * @param  non-empty-string  $expected
     * @return self<TValue>
     */
    public function to_start_with(string $expected, string $message = ''): self
    {
        if (!is_string($this->value)) {
            Invalid_Expectation_Value::expected('string');
        }
        Assert::assert_string_starts_with($expected, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value ends with $expected.
     *
     * @param  non-empty-string  $expected
     * @return self<TValue>
     */
    public function to_end_with(string $expected, string $message = ''): self
    {
        if (!is_string($this->value)) {
            Invalid_Expectation_Value::expected('string');
        }
        Assert::assert_string_ends_with($expected, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that $number matches value's Length.
     *
     * @return self<TValue>
     */
    public function to_have_length(int $number, string $message = ''): self
    {
        if (is_string($this->value)) {
            Assert::assert_equals($number, mb_strlen($this->value), $message);
            return $this;
        }
        if (is_iterable($this->value)) {
            return $this->to_have_count($number, $message);
        }
        if (is_object($this->value)) {
            $array = method_exists($this->value, 'toArray') ? $this->value->to_array() : (array) $this->value;
            Assert::assert_count($number, $array, $message);
            return $this;
        }
        throw new BadMethodCallException('Expectation value length is not countable.');
    }
    /**
     * Asserts that $count matches the number of elements of the value.
     *
     * @return self<TValue>
     */
    public function to_have_count(int $count, string $message = ''): self
    {
        if (!is_countable($this->value) && !is_iterable($this->value)) {
            Invalid_Expectation_Value::expected('countable|iterable');
        }
        Assert::assert_count($count, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the size of the value and $expected are the same.
     *
     * @param  Countable|iterable<mixed>  $expected
     * @return self<TValue>
     */
    public function to_have_same_size(Countable|iterable $expected, string $message = ''): self
    {
        if (!is_countable($this->value) && !is_iterable($this->value)) {
            Invalid_Expectation_Value::expected('countable|iterable');
        }
        Assert::assert_same_size($expected, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value contains the property $name.
     *
     * @return self<TValue>
     */
    public function to_have_property(string $name, mixed $value = new Any(), string $message = ''): self
    {
        $this->to_be_object();
        // @phpstan-ignore-next-line
        Assert::assert_true(property_exists($this->value, $name), $message);
        if (!$value instanceof Any) {
            /* @phpstan-ignore-next-line */
            Assert::assert_equals($value, $this->value->{$name}, $message);
        }
        return $this;
    }
    /**
     * Asserts that the value contains the provided properties $names.
     *
     * @param  iterable<string, mixed>|iterable<int, string>  $names
     * @return self<TValue>
     */
    public function to_have_properties(iterable $names, string $message = ''): self
    {
        foreach ($names as $name => $value) {
            is_int($name) ? $this->to_have_property($value, message: $message) : $this->to_have_property($name, $value, $message);
            // @phpstan-ignore-line
        }
        return $this;
    }
    /**
     * Asserts that two variables have the same value.
     *
     * @return self<TValue>
     */
    public function to_equal(mixed $expected, string $message = ''): self
    {
        Assert::assert_equals($expected, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that two variables have the same value.
     * The contents of $expected and the $this->value are
     * canonicalized before they are compared. For instance, when the two
     * variables $expected and $this->value are arrays, then these arrays
     * are sorted before they are compared. When $expected and $this->value
     * are objects, each object is converted to an array containing all
     * private, protected and public attributes.
     *
     * @return self<TValue>
     */
    public function to_equal_canonicalizing(mixed $expected, string $message = ''): self
    {
        Assert::assert_equals_canonicalizing($expected, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the absolute difference between the value and $expected
     * is lower than $delta.
     *
     * @return self<TValue>
     */
    public function to_equal_with_delta(mixed $expected, float $delta, string $message = ''): self
    {
        Assert::assert_equals_with_delta($expected, $this->value, $delta, $message);
        return $this;
    }
    /**
     * Asserts that the value is one of the given values.
     *
     * @param  iterable<int|string, mixed>  $values
     * @return self<TValue>
     */
    public function to_be_in(iterable $values, string $message = ''): self
    {
        Assert::assert_contains($this->value, $values, $message);
        return $this;
    }
    /**
     * Asserts that the value is infinite.
     *
     * @return self<TValue>
     */
    public function to_be_infinite(string $message = ''): self
    {
        Assert::assert_infinite($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is an instance of $class.
     *
     * @param  class-string  $class
     * @return self<TValue>
     */
    public function to_be_instance_of(string $class, string $message = ''): self
    {
        Assert::assert_instance_of($class, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is an array.
     *
     * @return self<TValue>
     */
    public function to_be_array(string $message = ''): self
    {
        Assert::assert_is_array($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is a list.
     *
     * @return self<TValue>
     */
    public function to_be_list(string $message = ''): self
    {
        Assert::assert_is_list($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is of type bool.
     *
     * @return self<TValue>
     */
    public function to_be_bool(string $message = ''): self
    {
        Assert::assert_is_bool($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is of type callable.
     *
     * @return self<TValue>
     */
    public function to_be_callable(string $message = ''): self
    {
        Assert::assert_is_callable($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is of type float.
     *
     * @return self<TValue>
     */
    public function to_be_float(string $message = ''): self
    {
        Assert::assert_is_float($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is of type int.
     *
     * @return self<TValue>
     */
    public function to_be_int(string $message = ''): self
    {
        Assert::assert_is_int($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is of type iterable.
     *
     * @return self<TValue>
     */
    public function to_be_iterable(string $message = ''): self
    {
        Assert::assert_is_iterable($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is of type numeric.
     *
     * @return self<TValue>
     */
    public function to_be_numeric(string $message = ''): self
    {
        Assert::assert_is_numeric($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value contains only digits.
     *
     * @return self<TValue>
     */
    public function to_be_digits(string $message = ''): self
    {
        Assert::assert_true(ctype_digit((string) $this->value), $message);
        return $this;
    }
    /**
     * Asserts that the value is of type object.
     *
     * @return self<TValue>
     */
    public function to_be_object(string $message = ''): self
    {
        Assert::assert_is_object($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is of type resource.
     *
     * @return self<TValue>
     */
    public function to_be_resource(string $message = ''): self
    {
        Assert::assert_is_resource($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is of type scalar.
     *
     * @return self<TValue>
     */
    public function to_be_scalar(string $message = ''): self
    {
        Assert::assert_is_scalar($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is of type string.
     *
     * @return self<TValue>
     */
    public function to_be_string(string $message = ''): self
    {
        Assert::assert_is_string($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is a JSON string.
     *
     * @return self<TValue>
     */
    public function to_be_json(string $message = ''): self
    {
        Assert::assert_is_string($this->value, $message);
        Assert::assert_json($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is NAN.
     *
     * @return self<TValue>
     */
    public function to_be_nan(string $message = ''): self
    {
        Assert::assert_nan($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is null.
     *
     * @return self<TValue>
     */
    public function to_be_null(string $message = ''): self
    {
        Assert::assert_null($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value array has the provided $key.
     *
     * @return self<TValue>
     */
    public function to_have_key(string|int $key, mixed $value = new Any(), string $message = ''): self
    {
        if (is_object($this->value) && method_exists($this->value, 'toArray')) {
            $array = $this->value->to_array();
        } else {
            $array = (array) $this->value;
        }
        try {
            Assert::assert_true(Arr::has($array, $key));
            /* @phpstan-ignore-next-line */
        } catch (Expectation_Failed_Exception $exception) {
            if ($message === '') {
                $message = "Failed asserting that an array has the key '{$key}'";
            }
            throw new Expectation_Failed_Exception($message, $exception->get_comparison_failure());
        }
        if (!$value instanceof Any) {
            Assert::assert_equals($value, Arr::get($array, $key), $message);
        }
        return $this;
    }
    /**
     * Asserts that the value array has the provided $keys.
     *
     * @param  array<int, int|string|array<array-key, mixed>>  $keys
     * @return self<TValue>
     */
    public function to_have_keys(array $keys, string $message = ''): self
    {
        foreach ($keys as $k => $key) {
            if (is_array($key)) {
                $this->to_have_keys(array_keys(Arr::dot($key, $k . '.')), $message);
            } else {
                $this->to_have_key($key, message: $message);
            }
        }
        return $this;
    }
    /**
     * Asserts that the value is a directory.
     *
     * @return self<TValue>
     */
    public function to_be_directory(string $message = ''): self
    {
        if (!is_string($this->value)) {
            Invalid_Expectation_Value::expected('string');
        }
        Assert::assert_directory_exists($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is a directory and is readable.
     *
     * @return self<TValue>
     */
    public function to_be_readable_directory(string $message = ''): self
    {
        if (!is_string($this->value)) {
            Invalid_Expectation_Value::expected('string');
        }
        Assert::assert_directory_is_readable($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is a directory and is writable.
     *
     * @return self<TValue>
     */
    public function to_be_writable_directory(string $message = ''): self
    {
        if (!is_string($this->value)) {
            Invalid_Expectation_Value::expected('string');
        }
        Assert::assert_directory_is_writable($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is a file.
     *
     * @return self<TValue>
     */
    public function to_be_file(string $message = ''): self
    {
        if (!is_string($this->value)) {
            Invalid_Expectation_Value::expected('string');
        }
        Assert::assert_file_exists($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is a file and is readable.
     *
     * @return self<TValue>
     */
    public function to_be_readable_file(string $message = ''): self
    {
        if (!is_string($this->value)) {
            Invalid_Expectation_Value::expected('string');
        }
        Assert::assert_file_is_readable($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is a file and is writable.
     *
     * @return self<TValue>
     */
    public function to_be_writable_file(string $message = ''): self
    {
        if (!is_string($this->value)) {
            Invalid_Expectation_Value::expected('string');
        }
        Assert::assert_file_is_writable($this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value array matches the given array subset.
     *
     * @param  iterable<int|string, mixed>  $array
     * @return self<TValue>
     */
    public function to_match_array(iterable $array, string $message = ''): self
    {
        if (is_object($this->value) && method_exists($this->value, 'toArray')) {
            $value_as_array = $this->value->to_array();
        } else {
            $value_as_array = (array) $this->value;
        }
        foreach ($array as $key => $value) {
            Assert::assert_array_has_key($key, $value_as_array, $message);
            $assert_message = $message !== '' ? $message : sprintf('Failed asserting that an array has a key %s with the value %s.', $this->export($key), $this->export($value_as_array[$key]));
            Assert::assert_equals($value, $value_as_array[$key], $assert_message);
        }
        return $this;
    }
    /**
     * Asserts that the value object matches a subset
     * of the properties of an given object.
     *
     * @param  iterable<string, mixed>  $object
     * @return self<TValue>
     */
    public function to_match_object(object|iterable $object, string $message = ''): self
    {
        foreach ((array) $object as $property => $value) {
            if (!is_object($this->value) && !is_string($this->value)) {
                Invalid_Expectation_Value::expected('object|string');
            }
            Assert::assert_true(property_exists($this->value, $property), $message);
            /* @phpstan-ignore-next-line */
            $property_value = $this->value->{$property};
            $assert_message = $message !== '' ? $message : sprintf('Failed asserting that an object has a property %s with the value %s.', $this->export($property), $this->export($property_value));
            Assert::assert_equals($value, $property_value, $assert_message);
        }
        return $this;
    }
    /**
     * Asserts that the value "stringable" matches the given snapshot..
     *
     * @return self<TValue>
     */
    public function to_match_snapshot(string $message = ''): self
    {
        $snapshots = Test_Suite::get_instance()->snapshots;
        $snapshots->start_new_expectation();
        $test_case = Test_Suite::get_instance()->test;
        assert($test_case instanceof Test_Case);
        $string = match (true) {
            is_string($this->value) => $this->value,
            is_object($this->value) && method_exists($this->value, 'toSnapshot') => $this->value->to_snapshot(),
            is_object($this->value) && method_exists($this->value, '__toString') => $this->value->__toString(),
            is_object($this->value) && method_exists($this->value, 'toString') => $this->value->to_string(),
            $this->value instanceof Test_Response => $this->value->get_content(),
            // @phpstan-ignore-line
            is_array($this->value) => json_encode($this->value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            $this->value instanceof Traversable => json_encode(iterator_to_array($this->value), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            $this->value instanceof JsonSerializable => json_encode($this->value->jsonSerialize(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            is_object($this->value) && method_exists($this->value, 'toArray') => json_encode($this->value->to_array(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            default => Invalid_Expectation_Value::expected('array|object|string'),
        };
        if ($snapshots->has()) {
            [$filename, $content] = $snapshots->get();
            Assert::assert_same(strtr($content, ["\r\n" => "\n", "\r" => "\n"]), strtr($string, ["\r\n" => "\n", "\r" => "\n"]), $message === '' ? "Failed asserting that the string value matches its snapshot ({$filename})." : $message);
        } else {
            $filename = $snapshots->save($string);
            Test_Suite::get_instance()->register_snapshot_change("Snapshot created at [{$filename}]");
        }
        return $this;
    }
    /**
     * Asserts that the value matches a regular expression.
     *
     * @return self<TValue>
     */
    public function to_match(string $expression, string $message = ''): self
    {
        if (!is_string($this->value)) {
            Invalid_Expectation_Value::expected('string');
        }
        Assert::assert_matches_regular_expression($expression, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value matches a constraint.
     *
     * @return self<TValue>
     */
    public function to_match_constraint(Constraint $constraint, string $message = ''): self
    {
        Assert::assert_that($this->value, $constraint, $message);
        return $this;
    }
    /**
     * @param  class-string  $class
     * @return self<TValue>
     */
    public function to_contain_only_instances_of(string $class, string $message = ''): self
    {
        if (!is_iterable($this->value)) {
            Invalid_Expectation_Value::expected('iterable');
        }
        Assert::assert_contains_only_instances_of($class, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that executing value throws an exception.
     *
     * @param  (Closure(Throwable): mixed)|string  $exception
     * @return self<TValue>
     */
    public function to_throw(callable|string|Throwable $exception, ?string $exception_message = null, string $message = ''): self
    {
        $callback = Null_Closure::create();
        if ($exception instanceof Closure) {
            $callback = $exception;
            $parameters = (new ReflectionFunction($exception))->get_parameters();
            if (count($parameters) !== 1) {
                throw new InvalidArgumentException('The given closure must have a single parameter type-hinted as the class string.');
            }
            if (!($type = $parameters[0]->get_type()) instanceof ReflectionNamedType) {
                throw new InvalidArgumentException('The given closure\'s parameter must be type-hinted as the class string.');
            }
            $exception = $type->get_name();
        }
        try {
            ($this->value)();
        } catch (Throwable $e) {
            if ($exception instanceof Throwable) {
                expect($e)->to_be_instance_of($exception::class, $message)->and($e->get_message())->to_be($exception_message ?? $exception->get_message(), $message);
                return $this;
            }
            if (!class_exists($exception)) {
                if ($e instanceof Error && "Class \"{$exception}\" not found" === $e->get_message()) {
                    Assert::assert_true(true);
                    throw $e;
                }
                Assert::assert_string_contains_string($exception, $e->get_message(), $message);
                return $this;
            }
            if ($exception_message !== null) {
                Assert::assert_string_contains_string($exception_message, $e->get_message(), $message);
            }
            Assert::assert_instance_of($exception, $e, $message);
            $callback($e);
            return $this;
        }
        Assert::assert_true(true);
        if (!$exception instanceof Throwable && !class_exists($exception)) {
            throw new Expectation_Failed_Exception("Exception with message \"{$exception}\" not thrown.");
        }
        throw new Expectation_Failed_Exception("Exception \"{$exception}\" not thrown.");
    }
    /**
     * Exports the given value.
     */
    private function export(mixed $value): string
    {
        if (!$this->exporter instanceof Exporter) {
            $this->exporter = Exporter::default();
        }
        return $this->exporter->shortened_export($value);
    }
    /**
     * Asserts that the value is uppercase.
     *
     * @return self<TValue>
     */
    public function to_be_uppercase(string $message = ''): self
    {
        Assert::assert_true(ctype_upper((string) $this->value), $message);
        return $this;
    }
    /**
     * Asserts that the value is lowercase.
     *
     * @return self<TValue>
     */
    public function to_be_lowercase(string $message = ''): self
    {
        Assert::assert_true(ctype_lower((string) $this->value), $message);
        return $this;
    }
    /**
     * Asserts that the value is alphanumeric.
     *
     * @return self<TValue>
     */
    public function to_be_alpha_numeric(string $message = ''): self
    {
        Assert::assert_true(ctype_alnum((string) $this->value), $message);
        return $this;
    }
    /**
     * Asserts that the value is alpha.
     *
     * @return self<TValue>
     */
    public function to_be_alpha(string $message = ''): self
    {
        Assert::assert_true(ctype_alpha((string) $this->value), $message);
        return $this;
    }
    /**
     * Asserts that the value is snake_case.
     *
     * @return self<TValue>
     */
    public function to_be_snake_case(string $message = ''): self
    {
        $value = (string) $this->value;
        if ($message === '') {
            $message = "Failed asserting that {$value} is snake_case.";
        }
        Assert::assert_true((bool) preg_match('/^[\p{Ll}_]+$/u', $value), $message);
        return $this;
    }
    /**
     * Asserts that the value is kebab-case.
     *
     * @return self<TValue>
     */
    public function to_be_kebab_case(string $message = ''): self
    {
        $value = (string) $this->value;
        if ($message === '') {
            $message = "Failed asserting that {$value} is kebab-case.";
        }
        Assert::assert_true((bool) preg_match('/^[\p{Ll}-]+$/u', $value), $message);
        return $this;
    }
    /**
     * Asserts that the value is camelCase.
     *
     * @return self<TValue>
     */
    public function to_be_camel_case(string $message = ''): self
    {
        $value = (string) $this->value;
        if ($message === '') {
            $message = "Failed asserting that {$value} is camelCase.";
        }
        Assert::assert_true((bool) preg_match('/^\p{Ll}[\p{Ll}\p{Lu}]+$/u', $value), $message);
        return $this;
    }
    /**
     * Asserts that the value is StudlyCase.
     *
     * @return self<TValue>
     */
    public function to_be_studly_case(string $message = ''): self
    {
        $value = (string) $this->value;
        if ($message === '') {
            $message = "Failed asserting that {$value} is StudlyCase.";
        }
        Assert::assert_true((bool) preg_match('/^\p{Lu}+\p{Ll}[\p{Ll}\p{Lu}]+$/u', $value), $message);
        return $this;
    }
    /**
     * Asserts that the value is UUID.
     *
     * @return self<TValue>
     */
    public function to_be_uuid(string $message = ''): self
    {
        if (!is_string($this->value)) {
            Invalid_Expectation_Value::expected('string');
        }
        Assert::assert_true(Str::is_uuid($this->value), $message);
        return $this;
    }
    /**
     * Asserts that the value is between 2 specified values
     *
     * @return self<TValue>
     */
    public function to_be_between(int|float|DateTimeInterface $lowest_value, int|float|DateTimeInterface $highest_value, string $message = ''): self
    {
        Assert::assert_greater_than_or_equal($lowest_value, $this->value, $message);
        Assert::assert_less_than_or_equal($highest_value, $this->value, $message);
        return $this;
    }
    /**
     * Asserts that the value is a url
     *
     * @return self<TValue>
     */
    public function to_be_url(string $message = ''): self
    {
        if ($message === '') {
            $message = "Failed asserting that {$this->value} is a url.";
        }
        Assert::assert_true(Str::is_url((string) $this->value), $message);
        return $this;
    }
    /**
     * Asserts that the value can be converted to a slug
     *
     * @return self<TValue>
     */
    public function to_be_slug(string $message = ''): self
    {
        if ($message === '') {
            $message = "Failed asserting that {$this->value} can be converted to a slug.";
        }
        $slug = Str::slugify((string) $this->value);
        Assert::assert_not_empty($slug, $message);
        return $this;
    }
}