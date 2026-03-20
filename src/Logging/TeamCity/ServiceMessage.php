<?php

declare (strict_types=1);
namespace Pest\Logging\Team_City;

/**
 * @internal
 */
final class Service_Message
{
    /**
     * The flow ID.
     */
    private static ?int $flow_id = null;
    /**
     * @param  array<string, string|int|null>  $parameters
     */
    public function __construct(private readonly string $type, private readonly array $parameters)
    {
    }
    public function to_string(): string
    {
        $params_to_string = '';
        foreach ([...$this->parameters, 'flowId' => self::$flow_id] as $key => $value) {
            $value = $this->escape_service_message((string) $value);
            $params_to_string .= " {$key}='{$value}'";
        }
        return "##teamcity[{$this->type}{$params_to_string}]";
    }
    public static function test_suite_started(string $name, ?string $location): self
    {
        return new self('testSuiteStarted', ['name' => $name, 'locationHint' => $location === null ? null : "pest_qn://{$location}"]);
    }
    public static function test_suite_count(int $count): self
    {
        return new self('testCount', ['count' => $count]);
    }
    public static function test_suite_finished(string $name): self
    {
        return new self('testSuiteFinished', ['name' => $name]);
    }
    public static function test_started(string $name, string $location): self
    {
        return new self('testStarted', ['name' => $name, 'locationHint' => "pest_qn://{$location}"]);
    }
    /**
     * @param  int  $duration  in milliseconds
     */
    public static function test_finished(string $name, int $duration): self
    {
        return new self('testFinished', ['name' => $name, 'duration' => $duration]);
    }
    public static function test_std_out(string $name, string $data): self
    {
        if (!str_ends_with($data, "\n")) {
            $data .= "\n";
        }
        return new self('testStdOut', ['name' => $name, 'out' => $data]);
    }
    public static function test_failed(string $name, string $message, string $details): self
    {
        return new self('testFailed', ['name' => $name, 'message' => $message, 'details' => $details]);
    }
    public static function test_std_err(string $name, string $data): self
    {
        if (!str_ends_with($data, "\n")) {
            $data .= "\n";
        }
        return new self('testStdErr', ['name' => $name, 'out' => $data]);
    }
    public static function test_ignored(string $name, string $message, ?string $details = null): self
    {
        return new self('testIgnored', ['name' => $name, 'message' => $message, 'details' => $details]);
    }
    public static function comparison_failure(string $name, string $message, string $details, string $actual, string $expected): self
    {
        return new self('testFailed', ['name' => $name, 'message' => $message, 'details' => $details, 'type' => 'comparisonFailure', 'actual' => $actual, 'expected' => $expected]);
    }
    private function escape_service_message(string $text): string
    {
        return str_replace(['|', "'", "\n", "\r", ']', '['], ['||', "|'", '|n', '|r', '|]', '|['], $text);
    }
    public static function set_flow_id(int $flow_id): void
    {
        self::$flow_id = $flow_id;
    }
}