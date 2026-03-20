<?php

declare (strict_types=1);
namespace Pest\Support;

use Closure;
use Php_Unit\Framework\Test_Case;
use Throwable;
/**
 * @internal
 */
final class Exception_Trace
{
    private const string UNDEFINED_METHOD = 'Call to undefined method P\\';
    /**
     * Ensures the given closure reports the good execution context.
     *
     * @throws Throwable
     */
    public static function ensure(Closure $closure): mixed
    {
        try {
            return $closure();
        } catch (Throwable $throwable) {
            if (Str::starts_with($message = $throwable->get_message(), self::UNDEFINED_METHOD)) {
                $class = preg_match('/^Call to undefined method ([^:]+)::/', $message, $matches) === false ? null : $matches[1];
                $message = str_replace(self::UNDEFINED_METHOD, 'Call to undefined method ', $message);
                if (class_exists((string) $class) && (is_countable(class_parents($class)) ? count(class_parents($class)) : 0) > 0 && array_values(class_parents($class))[0] === Test_Case::class) {
                    // @phpstan-ignore-line
                    $message .= '. Did you forget to use the [pest()->extend()] function? Read more at: https://pestphp.com/docs/configuring-tests';
                }
                Reflection::set_property_value($throwable, 'message', $message);
            }
            throw $throwable;
        }
    }
}