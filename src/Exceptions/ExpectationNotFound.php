<?php

declare (strict_types=1);
namespace Pest\Exceptions;

use Exception;
/**
 * @internal
 */
final class Expectation_Not_Found extends Exception
{
    /**
     * Creates a new ExpectationNotFound instance from the given name.
     */
    public static function from_name(string $name): Expectation_Not_Found
    {
        return new self("Expectation [{$name}] does not exist.");
    }
}