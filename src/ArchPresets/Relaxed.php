<?php

declare (strict_types=1);
namespace Pest\Arch_Presets;

use Pest\Arch\Contracts\Arch_Expectation;
use Pest\Expectation;
/**
 * @internal
 */
final class Relaxed extends Abstract_Preset
{
    /**
     * Executes the arch preset.
     */
    public function execute(): void
    {
        $this->each_user_namespace(fn(Expectation $namespace): Arch_Expectation => $namespace->not->to_use_strict_types(), fn(Expectation $namespace): Arch_Expectation => $namespace->classes()->not->to_be_final(), fn(Expectation $namespace): Arch_Expectation => $namespace->classes()->not->to_have_private_methods());
    }
}