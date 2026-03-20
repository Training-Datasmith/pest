<?php

declare (strict_types=1);
namespace Pest\Arch_Presets;

use Closure;
use Pest\Arch\Contracts\Arch_Expectation;
use Pest\Expectation;
/**
 * @internal
 */
final class Custom extends Abstract_Preset
{
    /**
     * Creates a new preset instance.
     *
     * @param  array<int, string>  $userNamespaces
     * @param  Closure(array<int, string>): array<Expectation<mixed>|ArchExpectation>  $execute
     */
    public function __construct(private readonly array $user_namespaces, private readonly string $name, private readonly Closure $execute)
    {
        parent::__construct($user_namespaces);
    }
    /**
     * Returns the name of the preset.
     */
    public function name(): string
    {
        return $this->name;
    }
    /**
     * Executes the arch preset.
     */
    public function execute(): void
    {
        $this->expectations = ($this->execute)($this->user_namespaces);
    }
}