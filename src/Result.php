<?php

declare (strict_types=1);
namespace Pest;

use Php_Unit\Test_Runner\Test_Result\Test_Result;
use Php_Unit\Text_Ui\Configuration\Configuration;
use Php_Unit\Text_Ui\Shell_Exit_Code_Calculator;
/**
 * @internal
 */
final class Result
{
    private const int SUCCESS_EXIT = 0;
    /**
     * If the exit code is different from 0.
     */
    public static function failed(Configuration $configuration, Test_Result $result): bool
    {
        return !self::ok($configuration, $result);
    }
    /**
     * If the exit code is exactly 0.
     */
    public static function ok(Configuration $configuration, Test_Result $result): bool
    {
        return self::exit_code($configuration, $result) === self::SUCCESS_EXIT;
    }
    /**
     * Get the test execution's exit code.
     */
    public static function exit_code(Configuration $configuration, Test_Result $result): int
    {
        $shell = new Shell_Exit_Code_Calculator();
        return $shell->calculate($configuration, $result);
    }
}