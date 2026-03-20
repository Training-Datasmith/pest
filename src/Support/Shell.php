<?php

declare (strict_types=1);
namespace Pest\Support;

use Illuminate\Support\Env;
use Laravel\Tinker\Class_Alias_Autoloader;
use Pest\Test_Suite;
use Psy\Configuration;
use Psy\Shell as PsyShell;
use Psy\Version_Updater\Checker;
/**
 * @internal
 */
final class Shell
{
    /**
     * Creates a new interactive shell.
     */
    public static function open(): void
    {
        $config = new Configuration();
        $config->set_update_check(Checker::NEVER);
        $config->get_presenter()->add_casters(self::casters());
        $shell = new Psy_Shell($config);
        $loader = self::tinkered($shell);
        try {
            $shell->run();
        } finally {
            $loader?->unregister();
            // @phpstan-ignore-line
        }
    }
    /**
     * Returns the casters for the Psy Shell.
     *
     * @return array<string, callable>
     */
    private static function casters(): array
    {
        $casters = ['Illuminate\Support\Collection' => 'Laravel\Tinker\TinkerCaster::castCollection', 'Illuminate\Support\HtmlString' => 'Laravel\Tinker\TinkerCaster::castHtmlString', 'Illuminate\Support\Stringable' => 'Laravel\Tinker\TinkerCaster::castStringable'];
        if (class_exists('Illuminate\Database\Eloquent\Model')) {
            $casters['Illuminate\Database\Eloquent\Model'] = 'Laravel\Tinker\TinkerCaster::castModel';
        }
        if (class_exists('Illuminate\Process\ProcessResult')) {
            $casters['Illuminate\Process\ProcessResult'] = 'Laravel\Tinker\TinkerCaster::castProcessResult';
        }
        if (class_exists('Illuminate\Foundation\Application')) {
            $casters['Illuminate\Foundation\Application'] = 'Laravel\Tinker\TinkerCaster::castApplication';
        }
        if (function_exists('app') === false) {
            return $casters;
            // @phpstan-ignore-line
        }
        $config = app()->make('config');
        return array_merge($casters, (array) $config->get('tinker.casters', []));
    }
    /**
     * Tinkers the current shell, if the Tinker package is available.
     */
    private static function tinkered(Psy_Shell $shell): ?object
    {
        if (function_exists('app') === false || !class_exists(Env::class) || !class_exists(Class_Alias_Autoloader::class)) {
            return null;
        }
        $path = Env::get('COMPOSER_VENDOR_DIR', app()->base_path() . DIRECTORY_SEPARATOR . 'vendor');
        $path .= '/composer/autoload_classmap.php';
        if (!file_exists($path)) {
            $path = Test_Suite::get_instance()->root_path . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'autoload_classmap.php';
        }
        $config = app()->make('config');
        return Class_Alias_Autoloader::register($shell, $path, $config->get('tinker.alias', []), $config->get('tinker.dont_alias', []));
    }
}