<?php

declare (strict_types=1);
namespace Pest\Support;

use Closure;
/**
 * @internal
 */
final class Null_Closure
{
    /**
     * Creates a nullable closure.
     */
    public static function create(): Closure
    {
        return Closure::from_callable(function (): void {
        });
    }
}