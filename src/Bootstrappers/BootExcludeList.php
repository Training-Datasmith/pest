<?php

declare (strict_types=1);
namespace Pest\Bootstrappers;

use Pest\Contracts\Bootstrapper;
use Php_Unit\Util\Exclude_List;
/**
 * @internal
 */
final class Boot_Exclude_List implements Bootstrapper
{
    /**
     * The directories to exclude.
     *
     * @var array<int, non-empty-string>
     */
    private const array EXCLUDE_LIST = ['bin', 'overrides', 'resources', 'src', 'stubs'];
    /**
     * Boots the "exclude list" for PHPUnit to ignore Pest files.
     */
    public function boot(): void
    {
        $base_directory = dirname(__DIR__, 2);
        foreach (self::EXCLUDE_LIST as $directory) {
            Exclude_List::add_directory($base_directory . DIRECTORY_SEPARATOR . $directory);
        }
    }
}