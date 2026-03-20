<?php

declare (strict_types=1);
namespace Pest;

use Pest\Exceptions\Invalid_Pest_Command;
use Pest\Repositories\After_All_Repository;
use Pest\Repositories\After_Each_Repository;
use Pest\Repositories\Before_All_Repository;
use Pest\Repositories\Before_Each_Repository;
use Pest\Repositories\Snapshot_Repository;
use Pest\Repositories\Test_Repository;
use Pest\Support\Str;
use Php_Unit\Framework\Test_Case;
/**
 * @internal
 */
final class Test_Suite
{
    /**
     * Holds the current test case.
     */
    public ?Test_Case $test = null;
    /**
     * Holds the tests repository.
     */
    public Test_Repository $tests;
    /**
     * Holds the before each repository.
     */
    public Before_Each_Repository $before_each;
    /**
     * Holds the before all repository.
     */
    public Before_All_Repository $before_all;
    /**
     * Holds the after each repository.
     */
    public After_Each_Repository $after_each;
    /**
     * Holds the after all repository.
     */
    public After_All_Repository $after_all;
    /**
     * Holds the snapshots repository.
     */
    public Snapshot_Repository $snapshots;
    /**
     * Holds the root path.
     */
    public string $root_path;
    /**
     * Holds an instance of the test suite.
     */
    private static ?Test_Suite $instance = null;
    /**
     * Creates a new instance of the test suite.
     */
    public function __construct(string $root_path, public string $test_path)
    {
        $this->before_all = new Before_All_Repository();
        $this->before_each = new Before_Each_Repository();
        $this->tests = new Test_Repository();
        $this->after_each = new After_Each_Repository();
        $this->after_all = new After_All_Repository();
        $this->root_path = (string) realpath($root_path);
        $this->snapshots = new Snapshot_Repository($this->root_path, implode(DIRECTORY_SEPARATOR, [$this->root_path, $this->test_path]), implode(DIRECTORY_SEPARATOR, ['.pest', 'snapshots']));
    }
    /**
     * Returns the current instance of the test suite.
     */
    public static function get_instance(?string $root_path = null, ?string $test_path = null): Test_Suite
    {
        if (is_string($root_path) && is_string($test_path)) {
            self::$instance = new Test_Suite($root_path, $test_path);
            foreach (Plugin::$callables as $callable) {
                $callable();
            }
            return self::$instance;
        }
        if (!self::$instance instanceof self) {
            throw new Invalid_Pest_Command();
        }
        return self::$instance;
    }
    public function get_filename(): string
    {
        assert($this->test instanceof Test_Case);
        return (fn() => self::$__filename)->call($this->test, $this->test::class);
        // @phpstan-ignore-line
    }
    public function get_description(): string
    {
        assert($this->test instanceof Test_Case);
        $description = str_replace('__pest_evaluable_', '', $this->test->name());
        $dataset_as_string = str_replace('__pest_evaluable_', '', Str::evaluable($this->test->data_set_as_string()));
        return str_replace(' ', '_', $description . $dataset_as_string);
    }
    public function register_snapshot_change(string $message): void
    {
        assert($this->test instanceof Test_Case);
        (fn(): string => $this->__snapshot_changes[] = $message)->call($this->test, $this->test::class);
        // @phpstan-ignore-line
    }
}