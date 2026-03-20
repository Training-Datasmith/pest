<?php

declare (strict_types=1);
namespace Pest\Support;

use Pest\Exceptions\Should_Not_Happen;
/**
 * @internal
 */
final class Backtrace
{
    private const string FILE = 'file';
    private const int BACKTRACE_OPTIONS = DEBUG_BACKTRACE_IGNORE_ARGS;
    /**
     * Returns the current test file.
     */
    public static function test_file(): string
    {
        $current = null;
        foreach (debug_backtrace(self::BACKTRACE_OPTIONS) as $trace) {
            assert(array_key_exists(self::FILE, $trace));
            $trace_file = str_replace(DIRECTORY_SEPARATOR, '/', $trace[self::FILE]);
            if (Str::ends_with($trace_file, 'overrides/Runner/TestSuiteLoader.php') || Str::ends_with($trace_file, 'src/Bootstrappers/BootFiles.php')) {
                break;
            }
            $current = $trace;
        }
        if ($current === null) {
            throw Should_Not_Happen::from_message('Test file not found.');
        }
        return $current[self::FILE];
    }
    /**
     * Returns the current datasets file.
     */
    public static function datasets_file(): string
    {
        $current = null;
        foreach (debug_backtrace(self::BACKTRACE_OPTIONS) as $trace) {
            assert(array_key_exists(self::FILE, $trace));
            $trace_file = str_replace(DIRECTORY_SEPARATOR, '/', $trace[self::FILE]);
            if (Str::ends_with($trace_file, 'Bootstrappers/BootFiles.php') || Str::ends_with($trace_file, 'overrides/Runner/TestSuiteLoader.php')) {
                break;
            }
            $current = $trace;
        }
        if ($current === null) {
            throw Should_Not_Happen::from_message('Dataset file not found.');
        }
        return $current[self::FILE];
    }
    /**
     * Returns the filename that called the current function/method.
     */
    public static function file(): string
    {
        $trace = self::backtrace();
        return $trace[self::FILE];
    }
    /**
     * Returns the dirname that called the current function/method.
     */
    public static function dirname(): string
    {
        $trace = self::backtrace();
        return dirname($trace[self::FILE]);
    }
    /**
     * Returns the line that called the current function/method.
     */
    public static function line(): int
    {
        $trace = self::backtrace();
        return $trace['line'] ?? 0;
    }
    /**
     * @return array{function: string, line?: int, file: string, class?: class-string, type?: string, args?: mixed[], object?: object}
     */
    private static function backtrace(): array
    {
        $backtrace = debug_backtrace(self::BACKTRACE_OPTIONS);
        foreach ($backtrace as $trace) {
            if (!isset($trace['file'])) {
                continue;
            }
            if (($GLOBALS['__PEST_INTERNAL_TEST_SUITE'] ?? false) && str_contains($trace['file'], 'pest' . DIRECTORY_SEPARATOR . 'src')) {
                continue;
            }
            if (str_contains($trace['file'], DIRECTORY_SEPARATOR . 'pestphp' . DIRECTORY_SEPARATOR . 'pest' . DIRECTORY_SEPARATOR . 'src')) {
                continue;
            }
            return $trace;
        }
        throw Should_Not_Happen::from_message('Backtrace not found.');
    }
}