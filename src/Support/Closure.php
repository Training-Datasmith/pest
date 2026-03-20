<?php

declare (strict_types=1);
namespace Pest\Support;

use Closure as BaseClosure;
use Pest\Exceptions\Should_Not_Happen;
/**
 * @internal
 */
final class Closure
{
    /**
     * Binds the given closure to the given "this".
     *
     * @throws ShouldNotHappen
     */
    public static function bind(?Base_Closure $closure, ?object $new_this, object|string|null $new_scope = 'static'): Base_Closure
    {
        if (!$closure instanceof Base_Closure) {
            throw Should_Not_Happen::from_message('Could not bind null closure.');
        }
        // @phpstan-ignore-next-line
        $closure = Base_Closure::bind($closure, $new_this, $new_scope);
        if (!$closure instanceof Base_Closure) {
            throw Should_Not_Happen::from_message('Could not bind closure.');
        }
        return $closure;
    }
}