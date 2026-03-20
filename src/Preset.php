<?php

declare (strict_types=1);
namespace Pest;

use Closure;
use Pest\Arch\Support\Composer;
use Pest\Arch_Presets\Abstract_Preset;
use Pest\Arch_Presets\Custom;
use Pest\Arch_Presets\Laravel;
use Pest\Arch_Presets\Php;
use Pest\Arch_Presets\Relaxed;
use Pest\Arch_Presets\Security;
use Pest\Arch_Presets\Strict;
use Pest\Exceptions\InvalidArgumentException;
use Pest\Pending_Calls\Test_Call;
use stdClass;
/**
 * @internal
 */
final class Preset
{
    /**
     * The application / package base namespaces.
     *
     * @var ?array<int, string>
     */
    private static ?array $base_namespaces = null;
    /**
     * The custom presets.
     *
     * @var array<string, Closure>
     */
    private static array $custom_presets = [];
    /**
     * Creates a new preset instance.
     */
    public function __construct()
    {
    }
    /**
     * Uses the Pest php preset and returns the test call instance.
     */
    public function php(): Php
    {
        return $this->execute_preset(new Php($this->base_namespaces()));
    }
    /**
     * Uses the Pest laravel preset and returns the test call instance.
     */
    public function laravel(): Laravel
    {
        return $this->execute_preset(new Laravel($this->base_namespaces()));
    }
    /**
     * Uses the Pest strict preset and returns the test call instance.
     */
    public function strict(): Strict
    {
        return $this->execute_preset(new Strict($this->base_namespaces()));
    }
    /**
     * Uses the Pest security preset and returns the test call instance.
     */
    public function security(): Abstract_Preset
    {
        return $this->execute_preset(new Security($this->base_namespaces()));
    }
    /**
     * Uses the Pest relaxed preset and returns the test call instance.
     */
    public function relaxed(): Abstract_Preset
    {
        return $this->execute_preset(new Relaxed($this->base_namespaces()));
    }
    /**
     * Uses the Pest custom preset and returns the test call instance.
     *
     * @internal
     */
    public static function custom(string $name, Closure $execute): void
    {
        if (preg_match('/^[a-zA-Z]+$/', $name) === false) {
            throw new InvalidArgumentException('The preset name must only contain words from a-z or A-Z.');
        }
        self::$custom_presets[$name] = $execute;
    }
    /**
     * Dynamically handle calls to the class.
     *
     * @param  array<int, mixed>  $arguments
     *
     * @throws InvalidArgumentException
     */
    public function __call(string $name, array $arguments): Abstract_Preset
    {
        if (!array_key_exists($name, self::$custom_presets)) {
            $available_presets = [...['php', 'laravel', 'strict', 'security', 'relaxed'], ...array_keys(self::$custom_presets)];
            throw new InvalidArgumentException(sprintf('The preset [%s] does not exist. The available presets are [%s].', $name, implode(', ', $available_presets)));
        }
        return $this->execute_preset(new Custom($this->base_namespaces(), $name, self::$custom_presets[$name]));
    }
    /**
     * Executes the given preset.
     *
     * @template TPreset of AbstractPreset
     *
     * @param  TPreset  $preset
     * @return TPreset
     */
    private function execute_preset(Abstract_Preset $preset): Abstract_Preset
    {
        $this->base_namespaces();
        $preset->execute();
        // $this->testCall->testCaseMethod->closure = (function () use ($preset): void {
        //    $preset->flush();
        // })->bindTo(new stdClass);
        return $preset;
    }
    /**
     * Get the base namespaces for the application / package.
     *
     * @return array<int, string>
     */
    private function base_namespaces(): array
    {
        if (self::$base_namespaces === null) {
            self::$base_namespaces = Composer::user_namespaces();
        }
        return self::$base_namespaces;
    }
}