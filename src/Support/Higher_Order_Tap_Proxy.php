<?php

declare (strict_types=1);
namespace Pest\Support;

use Php_Unit\Framework\Test_Case;
use ReflectionClass;
/**
 * @internal
 */
final class Higher_Order_Tap_Proxy
{
    /**
     * Create a new tap proxy instance.
     */
    public function __construct(public Test_Case $target)
    {
        // ..
    }
    /**
     * Dynamically sets properties on the target.
     */
    public function __set(string $property, mixed $value): void
    {
        $this->target->{$property} = $value;
    }
    /**
     * Dynamically pass properties gets to the target.
     */
    public function __get(string $property): mixed
    {
        if (property_exists($this->target, $property)) {
            return $this->target->{$property};
        }
        $class_name = (new ReflectionClass($this->target))->get_name();
        if (str_starts_with($class_name, 'P\\')) {
            $class_name = substr($class_name, 2);
        }
        trigger_error(sprintf('Undefined property %s::$%s', $class_name, $property), E_USER_WARNING);
        return null;
    }
    /**
     * Dynamically pass method calls to the target.
     *
     * @param  array<int, mixed>  $arguments
     * @return mixed
     */
    public function __call(string $method_name, array $arguments)
    {
        $filename = Backtrace::file();
        $line = Backtrace::line();
        return (new Higher_Order_Message($filename, $line, $method_name, $arguments))->call($this->target);
    }
}