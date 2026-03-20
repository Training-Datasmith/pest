<?php

declare (strict_types=1);
namespace Pest\Plugins;

use Pest\Contracts\Plugins\Terminable;
use Pest\Factories\Attribute;
use Pest\Factories\Test_Case_Method_Factory;
use Pest\Pending_Calls\Test_Call;
use Php_Unit\Framework\Attributes\Group;
/**
 * @internal
 */
final class Only implements Terminable
{
    /**
     * The temporary folder.
     */
    private const string TEMPORARY_FOLDER = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.temp';
    /**
     * Creates the lock file.
     */
    public static function enable(Test_Call|Test_Case_Method_Factory $test_call, string $group = '__pest_only'): void
    {
        if ($test_call instanceof Test_Call) {
            $test_call->group($group);
        } else {
            $test_call->attributes[] = new Attribute(Group::class, [$group]);
        }
        if (Environment::name() === Environment::CI || Parallel::is_worker()) {
            return;
        }
        $lock_file = self::TEMPORARY_FOLDER . DIRECTORY_SEPARATOR . 'only.lock';
        if (file_exists($lock_file) && $group === '__pest_only') {
            file_put_contents($lock_file, $group);
            return;
        }
        if (!file_exists($lock_file)) {
            touch($lock_file);
            file_put_contents($lock_file, $group);
        }
    }
    /**
     * Checks if "only" mode is enabled.
     */
    public static function is_enabled(): bool
    {
        $lock_file = self::TEMPORARY_FOLDER . DIRECTORY_SEPARATOR . 'only.lock';
        return file_exists($lock_file);
    }
    /**
     * Returns the group name.
     */
    public static function group(): string
    {
        $lock_file = self::TEMPORARY_FOLDER . DIRECTORY_SEPARATOR . 'only.lock';
        if (!file_exists($lock_file)) {
            return '__pest_only';
        }
        return file_get_contents($lock_file) ?: '__pest_only';
        // @phpstan-ignore-line
    }
    /**
     * {@inheritDoc}
     */
    public function terminate(): void
    {
        if (Parallel::is_worker()) {
            return;
        }
        $lock_file = self::TEMPORARY_FOLDER . DIRECTORY_SEPARATOR . 'only.lock';
        if (file_exists($lock_file)) {
            unlink($lock_file);
        }
    }
}