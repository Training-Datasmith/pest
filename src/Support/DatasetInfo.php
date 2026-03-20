<?php

declare (strict_types=1);
namespace Pest\Support;

use function Pest\Test_Directory;
/**
 * @internal
 */
final class Dataset_Info
{
    public const string DATASETS_DIR_NAME = 'Datasets';
    public const string DATASETS_FILE_NAME = 'Datasets.php';
    public static function is_inside_a_datasets_directory(string $file): bool
    {
        return basename(dirname($file)) === self::DATASETS_DIR_NAME;
    }
    public static function is_a_datasets_file(string $file): bool
    {
        return basename($file) === self::DATASETS_FILE_NAME;
    }
    public static function scope(string $file): string
    {
        if (Str::ends_with($file, test_directory('Pest.php'))) {
            return dirname($file);
        }
        if (self::is_inside_a_datasets_directory($file)) {
            return dirname($file, 2);
        }
        if (self::is_a_datasets_file($file)) {
            return dirname($file);
        }
        return $file;
    }
}