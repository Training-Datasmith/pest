<?php

declare (strict_types=1);
namespace Pest;

use Pest\Pending_Calls\Before_Each_Call;
use Pest\Pending_Calls\Uses_Call;
/**
 * @internal
 *
 * @mixin UsesCall
 */
final readonly class Configuration
{
    /**
     * The filename of the configuration.
     */
    private string $filename;
    /**
     * Creates a new configuration instance.
     */
    public function __construct(string $filename)
    {
        $this->filename = str_ends_with($filename, DIRECTORY_SEPARATOR . 'Pest.php') ? dirname($filename) : $filename;
    }
    /**
     * Use the given classes and traits in the given targets.
     */
    public function in(string ...$targets): Uses_Call
    {
        return (new Uses_Call($this->filename, []))->in(...$targets);
    }
    /**
     * Depending on where is called, it will extend the given classes and traits globally or locally.
     */
    public function extend(string ...$class_and_traits): Uses_Call
    {
        return new Uses_Call($this->filename, array_values($class_and_traits));
    }
    /**
     * Depending on where is called, it will extend the given classes and traits globally or locally.
     */
    public function extends(string ...$class_and_traits): Uses_Call
    {
        return $this->extend(...$class_and_traits);
    }
    /**
     * Depending on where is called, it will add the given groups globally or locally.
     */
    public function group(string ...$groups): Uses_Call
    {
        return (new Uses_Call($this->filename, []))->group(...$groups);
    }
    /**
     * Marks all tests in the current file to be run exclusively.
     */
    public function only(): void
    {
        (new Before_Each_Call(Test_Suite::get_instance(), $this->filename))->only();
    }
    /**
     * Depending on where is called, it will extend the given classes and traits globally or locally.
     */
    public function use(string ...$class_and_traits): Uses_Call
    {
        return $this->extend(...$class_and_traits);
    }
    /**
     * Depending on where is called, it will extend the given classes and traits globally or locally.
     */
    public function uses(string ...$class_and_traits): Uses_Call
    {
        return $this->extends(...$class_and_traits);
    }
    /**
     * Gets the printer configuration.
     */
    public function printer(): Configuration\Printer
    {
        return new Configuration\Printer();
    }
    /**
     * Gets the presets configuration.
     */
    public function presets(): Configuration\Presets
    {
        return new Configuration\Presets();
    }
    /**
     * Gets the project configuration.
     */
    public function project(): Configuration\Project
    {
        return Configuration\Project::get_instance();
    }
    /**
     * Gets the browser configuration.
     */
    public function browser(): Browser\Configuration
    {
        return new Browser\Configuration();
    }
    /**
     * Proxies calls to the uses method.
     *
     * @param  array<array-key, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->uses()->{$name}(...$arguments);
        // @phpstan-ignore-line
    }
}