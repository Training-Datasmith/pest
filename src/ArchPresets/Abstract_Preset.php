<?php

declare (strict_types=1);
namespace Pest\Arch_Presets;

use Pest\Arch\Contracts\Arch_Expectation;
use Pest\Expectation;
/**
 * @internal
 */
abstract class Abstract_Preset
{
    /**
     * The expectations.
     *
     * @var array<int, Expectation<mixed>|ArchExpectation>
     */
    protected array $expectations = [];
    /**
     * Creates a new preset instance.
     *
     * @param  array<int, string>  $userNamespaces
     */
    public function __construct(private readonly array $user_namespaces)
    {
    }
    /**
     * Executes the arch preset.
     *
     * @internal
     */
    abstract public function execute(): void;
    /**
     * Ignores the given "targets" or "dependencies".
     *
     * @param  array<int, string>|string  $targetsOrDependencies
     */
    final public function ignoring(array|string $targets_or_dependencies): void
    {
        $this->expectations = array_map(fn(Arch_Expectation|Expectation $expectation): Expectation|Arch_Expectation => $expectation instanceof Arch_Expectation ? $expectation->ignoring($targets_or_dependencies) : $expectation, $this->expectations);
    }
    /**
     * Runs the given callback for each namespace.
     *
     * @param  callable(Expectation<string|null>): ArchExpectation  ...$callbacks
     */
    final public function each_user_namespace(callable ...$callbacks): void
    {
        foreach ($this->user_namespaces as $namespace) {
            foreach ($callbacks as $callback) {
                $this->expectations[] = $callback(expect($namespace));
            }
        }
    }
    /**
     * Flushes the expectations.
     */
    final public function flush(): void
    {
        $this->expectations = [];
    }
}