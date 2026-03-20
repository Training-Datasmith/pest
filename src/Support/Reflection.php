<?php

declare (strict_types=1);
namespace Pest\Support;

use Closure;
use InvalidArgumentException;
use Pest\Exceptions\Should_Not_Happen;
use Pest\Test_Suite;
use Php_Unit\Framework\Test_Case;
use ReflectionClass;
use Reflection_Exception;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
/**
 * @internal
 */
final class Reflection
{
    /**
     * Calls the given method with args on the given object.
     *
     * @param  array<int, mixed>  $args
     */
    public static function call(object $object, string $method, array $args = []): mixed
    {
        $reflection_class = new ReflectionClass($object);
        try {
            $reflection_method = $reflection_class->get_method($method);
            return $reflection_method->invoke($object, ...$args);
        } catch (Reflection_Exception $exception) {
            if (method_exists($object, '__call')) {
                return $object->__call($method, $args);
            }
            if (is_callable($method)) {
                return self::bind_callable($method, $args);
            }
            throw $exception;
        }
    }
    /**
     * Bind a callable to the TestCase and return the result.
     *
     * @param  array<int, mixed>  $args
     */
    public static function bind_callable(callable $callable, array $args = []): mixed
    {
        return Closure::from_callable($callable)->bind_to(Test_Suite::get_instance()->test)(...$args);
    }
    /**
     * Bind a callable to the TestCase and return the result,
     * passing in the current dataset values as arguments.
     */
    public static function bind_callable_with_data(callable $callable): mixed
    {
        $test = Test_Suite::get_instance()->test;
        if (!$test instanceof Test_Case) {
            return self::bind_callable($callable);
        }
        foreach ($test->provided_data() as $value) {
            if ($value instanceof Closure) {
                throw new InvalidArgumentException('Bound datasets are not supported while doing high order testing.');
            }
        }
        return Closure::from_callable($callable)->bind_to($test)(...$test->provided_data());
    }
    /**
     * Infers the file name from the given closure.
     */
    public static function get_file_name_from_closure(Closure $closure): string
    {
        $reflection_closure = new ReflectionFunction($closure);
        return (string) $reflection_closure->get_file_name();
    }
    /**
     * Gets the property value from of the given object.
     */
    public static function get_property_value(object $object, string $property): mixed
    {
        $reflection_class = new ReflectionClass($object);
        $reflection_property = null;
        while (!$reflection_property instanceof ReflectionProperty) {
            try {
                /* @var ReflectionProperty $reflectionProperty */
                $reflection_property = $reflection_class->get_property($property);
            } catch (Reflection_Exception $reflection_exception) {
                $reflection_class = $reflection_class->get_parent_class();
                if (!$reflection_class instanceof ReflectionClass) {
                    throw new Should_Not_Happen($reflection_exception);
                }
            }
        }
        return $reflection_property->get_value($object);
    }
    /**
     * Sets the property value of the given object.
     *
     * @template TValue of object
     *
     * @param  TValue  $object
     */
    public static function set_property_value(object $object, string $property, mixed $value): void
    {
        /** @var ReflectionClass<TValue> $reflectionClass */
        $reflection_class = new ReflectionClass($object);
        $reflection_property = null;
        while (!$reflection_property instanceof ReflectionProperty) {
            try {
                /* @var ReflectionProperty $reflectionProperty */
                $reflection_property = $reflection_class->get_property($property);
            } catch (Reflection_Exception $reflection_exception) {
                $reflection_class = $reflection_class->get_parent_class();
                if (!$reflection_class instanceof ReflectionClass) {
                    throw new Should_Not_Happen($reflection_exception);
                }
            }
        }
        $reflection_property->set_value($object, $value);
    }
    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * @see https://github.com/laravel/framework/blob/v6.18.25/src/Illuminate/Support/Reflector.php
     */
    public static function get_parameter_class_name(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->get_type();
        if (!$type instanceof ReflectionNamedType) {
            return null;
        }
        if ($type->is_builtin()) {
            return null;
        }
        $name = $type->get_name();
        if (($class = $parameter->get_declaring_class()) instanceof ReflectionClass) {
            if ($name === 'self') {
                return $class->get_name();
            }
            if ($name === 'parent' && ($parent = $class->get_parent_class()) instanceof ReflectionClass) {
                return $parent->get_name();
            }
        }
        return $name;
    }
    /**
     * Receive a map of function argument names to their types.
     *
     * @return array<string, string>
     */
    public static function get_function_arguments(Closure $function): array
    {
        $parameters = (new ReflectionFunction($function))->get_parameters();
        $arguments = [];
        foreach ($parameters as $parameter) {
            /** @var ReflectionNamedType|ReflectionUnionType|null $types */
            $types = $parameter->has_type() ? $parameter->get_type() : null;
            if (is_null($types)) {
                $arguments[$parameter->get_name()] = 'mixed';
                continue;
            }
            $arguments[$parameter->get_name()] = implode('|', array_map(
                static fn(ReflectionNamedType $type): string => $type->get_name(),
                // @phpstan-ignore-line
                $types instanceof ReflectionNamedType ? [$types] : $types->get_types()
            ));
        }
        return $arguments;
    }
    public static function get_function_variable(Closure $function, string $key): mixed
    {
        return (new ReflectionFunction($function))->get_static_variables()[$key] ?? null;
    }
    /**
     * Get the properties from the given reflection class.
     *
     * Used by `expect()->toHavePropertiesDocumented()`.
     *
     * @param  ReflectionClass<object>  $reflectionClass
     * @return array<int, ReflectionProperty>
     */
    public static function get_properties_from_reflection_class(ReflectionClass $reflection_class): array
    {
        $get_properties = fn(ReflectionClass $reflection_class): array => array_filter(array_map(fn(ReflectionProperty $property): ReflectionProperty => $property, $reflection_class->get_properties()), fn(ReflectionProperty $property): bool => $property->get_declaring_class()->get_name() === $reflection_class->get_name());
        $properties_from_traits = [];
        foreach ($reflection_class->get_traits() as $trait) {
            $properties_from_traits = array_merge($properties_from_traits, $get_properties($trait));
        }
        $properties_from_traits = array_map(fn(ReflectionProperty $property): string => $property->get_name(), $properties_from_traits);
        return array_values(array_filter($get_properties($reflection_class), fn(ReflectionProperty $property): bool => !in_array($property->get_name(), $properties_from_traits, true)));
    }
    /**
     * Get the methods from the given reflection class.
     *
     * Used by `expect()->toHaveMethodsDocumented()`.
     *
     * @param  ReflectionClass<object>  $reflectionClass
     * @return array<int, ReflectionMethod>
     */
    public static function get_methods_from_reflection_class(ReflectionClass $reflection_class, int $filter = ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE): array
    {
        $get_methods = fn(ReflectionClass $reflection_class): array => array_filter(array_map(fn(ReflectionMethod $method): ReflectionMethod => $method, $reflection_class->get_methods($filter)), fn(ReflectionMethod $method): bool => $method->get_declaring_class()->get_name() === $reflection_class->get_name());
        $methods_from_traits = [];
        foreach ($reflection_class->get_traits() as $trait) {
            $methods_from_traits = array_merge($methods_from_traits, $get_methods($trait));
        }
        $methods_from_traits = array_map(fn(ReflectionMethod $method): string => $method->get_name(), $methods_from_traits);
        return array_values(array_filter($get_methods($reflection_class), fn(ReflectionMethod $method): bool => !in_array($method->get_name(), $methods_from_traits, true)));
    }
}