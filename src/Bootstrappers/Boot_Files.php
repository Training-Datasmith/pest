<?php

declare (strict_types=1);
namespace Pest\Bootstrappers;

use Pest\Contracts\Bootstrapper;
use Pest\Exceptions\Fatal_Exception;
use Pest\Support\Dataset_Info;
use Pest\Support\Str;
use function Pest\Test_Directory;
use Pest\Test_Suite;
use Recursive_Directory_Iterator;
use Recursive_Iterator_Iterator;
use Sebastian_Bergmann\File_Iterator\Facade as PhpUnitFileIterator;
/**
 * @internal
 */
final class Boot_Files implements Bootstrapper
{
    /**
     * The structure of the tests directory.
     *
     * @var array<int, string>
     */
    private const array STRUCTURE = ['Expectations', 'Expectations.php', 'Helpers', 'Helpers.php', 'Pest.php'];
    /**
     * Boots the structure of the tests directory.
     */
    public function boot(): void
    {
        $root_path = Test_Suite::get_instance()->root_path;
        $tests_path = $root_path . DIRECTORY_SEPARATOR . test_directory();
        if (!is_dir($tests_path)) {
            throw new Fatal_Exception(sprintf('The test directory [%s] does not exist.', $tests_path));
        }
        foreach (self::STRUCTURE as $filename) {
            $filename = sprintf('%s%s%s', $tests_path, DIRECTORY_SEPARATOR, $filename);
            if (!file_exists($filename)) {
                continue;
            }
            if (is_dir($filename)) {
                $directory = new Recursive_Directory_Iterator($filename);
                $iterator = new Recursive_Iterator_Iterator($directory);
                /** @var \DirectoryIterator $file */
                foreach ($iterator as $file) {
                    $this->load($file->__toString());
                }
            } else {
                $this->load($filename);
            }
        }
        $this->boot_datasets($tests_path);
    }
    /**
     * Loads, if possible, the given file.
     */
    private function load(string $filename): void
    {
        if (!Str::ends_with($filename, '.php')) {
            return;
        }
        if (!file_exists($filename)) {
            return;
        }
        include_once $filename;
    }
    private function boot_datasets(string $tests_path): void
    {
        assert($tests_path !== '');
        $files = (new Php_Unit_File_Iterator())->get_files_as_array($tests_path, '.php');
        foreach ($files as $file) {
            if (Dataset_Info::is_a_datasets_file($file) || Dataset_Info::is_inside_a_datasets_directory($file)) {
                $this->load($file);
            }
        }
    }
}