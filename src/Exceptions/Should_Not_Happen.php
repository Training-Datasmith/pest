<?php

declare (strict_types=1);
namespace Pest\Exceptions;

use Exception;
use RuntimeException;
/**
 * @internal
 */
final class Should_Not_Happen extends RuntimeException
{
    /**
     * Creates a new Exception instance.
     */
    public function __construct(Exception $exception)
    {
        $message = $exception->get_message();
        parent::__construct(sprintf(<<<'EOF'
        This should not happen - please create an new issue here: https://github.com/pestphp/pest/issues
        
          Issue: %s
          PHP version: %s
          Operating system: %s
        EOF, $message, phpversion(), PHP_OS), 1, $exception);
    }
    /**
     * Creates a new instance of should not happen without a specific exception.
     */
    public static function from_message(string $message): Should_Not_Happen
    {
        return new Should_Not_Happen(new Exception($message));
    }
}