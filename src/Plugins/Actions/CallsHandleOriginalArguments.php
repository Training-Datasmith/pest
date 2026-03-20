<?php

declare (strict_types=1);
namespace Pest\Plugins\Actions;

use Pest\Contracts\Plugins;
use Pest\Plugin\Loader;
/**
 * @internal
 */
final class Calls_Handle_Original_Arguments
{
    /**
     * Executes the Plugin action.
     *
     * Transform the input arguments by passing it to the relevant plugins.
     *
     * @param  array<int, string>  $argv
     */
    public static function execute(array $argv): void
    {
        $plugins = Loader::get_plugins(Plugins\Handles_Original_Arguments::class);
        /** @var Plugins\HandlesOriginalArguments $plugin */
        foreach ($plugins as $plugin) {
            $plugin->handle_original_arguments($argv);
        }
    }
}