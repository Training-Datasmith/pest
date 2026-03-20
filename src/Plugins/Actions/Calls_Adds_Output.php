<?php

declare (strict_types=1);
namespace Pest\Plugins\Actions;

use Pest\Contracts\Plugins;
use Pest\Plugin\Loader;
/**
 * @internal
 */
final class Calls_Adds_Output
{
    /**
     * Executes the Plugin action.
     *
     * Provides an opportunity for any plugins that want to provide additional output after test execution.
     */
    public static function execute(int $exit_code): int
    {
        $plugins = Loader::get_plugins(Plugins\Adds_Output::class);
        /** @var Plugins\AddsOutput $plugin */
        foreach ($plugins as $plugin) {
            $exit_code = $plugin->add_output($exit_code);
        }
        return $exit_code;
    }
}