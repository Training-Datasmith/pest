<?php

declare (strict_types=1);
namespace Pest\Plugins\Actions;

use Pest\Contracts\Plugins;
use Pest\Plugin\Loader;
/**
 * @internal
 */
final class Calls_Handle_Arguments
{
    /**
     * Executes the Plugin action.
     *
     * Transform the input arguments by passing it to the relevant plugins.
     *
     * @param  array<int, string>  $argv
     * @return array<int, string>
     */
    public static function execute(array $argv): array
    {
        $plugins = Loader::get_plugins(Plugins\Handles_Arguments::class);
        /** @var Plugins\HandlesArguments $plugin */
        foreach ($plugins as $plugin) {
            $argv = $plugin->handle_arguments($argv);
        }
        return $argv;
    }
}