<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Plugins\Concerns\Handle_Arguments;
use Php_Unit\Text_Ui\Cli_Arguments\Builder as CliConfigurationBuilder;
use Php_Unit\Text_Ui\Cli_Arguments\Xml_Configuration_File_Finder;
use Php_Unit\Text_Ui\Xml_Configuration\Default_Configuration;
use Php_Unit\Text_Ui\Xml_Configuration\Loader;
/**
 * @internal
 */
final class Cache implements Handles_Arguments
{
    use Handle_Arguments;
    /**
     * The temporary folder.
     */
    private const string TEMPORARY_FOLDER = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.temp';
    /**
     * Handles the arguments, adding the cache directory and the cache result arguments.
     */
    public function handle_arguments(array $arguments): array
    {
        if (!$this->has_argument('--cache-directory', $arguments)) {
            $cli_configuration = (new Cli_Configuration_Builder())->from_parameters([]);
            $configuration_file = (new Xml_Configuration_File_Finder())->find($cli_configuration);
            $xml_configuration = Default_Configuration::create();
            if (is_string($configuration_file)) {
                $xml_configuration = (new Loader())->load($configuration_file);
            }
            if (!$xml_configuration->phpunit()->has_cache_directory()) {
                $arguments = $this->push_argument('--cache-directory', $arguments);
                $arguments = $this->push_argument((string) realpath(self::TEMPORARY_FOLDER), $arguments);
            }
        }
        if (!$this->has_argument('--parallel', $arguments)) {
            return $this->push_argument('--cache-result', $arguments);
        }
        return $arguments;
    }
}