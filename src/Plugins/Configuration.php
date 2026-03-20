<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Dom_Document;
use Pest\Contracts\Plugins\Handles_Arguments;
use Pest\Contracts\Plugins\Terminable;
use Pest\Plugins\Concerns\Handle_Arguments;
use Php_Unit\Text_Ui\Cli_Arguments\Builder as CliConfigurationBuilder;
use Php_Unit\Text_Ui\Cli_Arguments\Xml_Configuration_File_Finder;
/**
 * @internal
 */
final class Configuration implements Handles_Arguments, Terminable
{
    use Handle_Arguments;
    /**
     * The base PHPUnit file.
     */
    public const string BASE_PHPUNIT_FILE = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources/base-phpunit.xml';
    /**
     * Handles the arguments, adding the cache directory and the cache result arguments.
     */
    public function handle_arguments(array $arguments): array
    {
        if ($this->has_argument('--configuration', $arguments) || $this->has_argument('-c', $arguments) || $this->has_custom_configuration_file()) {
            return $arguments;
        }
        $arguments = $this->push_argument('--configuration', $arguments);
        return $this->push_argument((string) realpath($this->from_generated_configuration_file()), $arguments);
    }
    /**
     * Get the configuration file from the generated configuration file.
     */
    private function from_generated_configuration_file(): string
    {
        $path = $this->get_temp_phpunit_xml_path();
        if (file_exists($path)) {
            unlink($path);
        }
        $doc = new Dom_Document();
        $doc->load(self::BASE_PHPUNIT_FILE);
        $contents = $doc->save_xml();
        assert(is_int(file_put_contents($path, $contents)));
        return $path;
    }
    /**
     * Check if the configuration file is custom.
     */
    private function has_custom_configuration_file(): bool
    {
        $cli_configuration = (new Cli_Configuration_Builder())->from_parameters([]);
        $configuration_file = (new Xml_Configuration_File_Finder())->find($cli_configuration);
        return is_string($configuration_file);
    }
    /**
     * Get the temporary phpunit.xml path.
     */
    private function get_temp_phpunit_xml_path(): string
    {
        return getcwd() . '/.pest.xml';
    }
    /**
     * Terminates the plugin.
     */
    public function terminate(): void
    {
        $path = $this->get_temp_phpunit_xml_path();
        if (file_exists($path)) {
            unlink($path);
        }
    }
}