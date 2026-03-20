<?php

declare (strict_types=1);
namespace Pest\Repositories;

use Pest\Exceptions\Should_Not_Happen;
use Pest\Test_Suite;
/**
 * @internal
 */
final class Snapshot_Repository
{
    /** @var array<string, int> */
    private static array $expectations_counter = [];
    /**
     * Creates a snapshot repository instance.
     */
    public function __construct(private readonly string $root_path, private readonly string $tests_path, private readonly string $snapshots_path)
    {
    }
    /**
     * Checks if the snapshot exists.
     */
    public function has(): bool
    {
        return file_exists($this->get_snapshot_filename());
    }
    /**
     * Gets the snapshot.
     *
     * @return array{0: string, 1: string}
     *
     * @throws ShouldNotHappen
     */
    public function get(): array
    {
        $contents = file_get_contents($snapshot_filename = $this->get_snapshot_filename());
        if ($contents === false) {
            throw Should_Not_Happen::from_message('Snapshot file could not be read.');
        }
        $snapshot = str_replace(dirname($this->tests_path) . '/', '', $snapshot_filename);
        return [$snapshot, $contents];
    }
    /**
     * Saves the given snapshot for the given test case.
     */
    public function save(string $snapshot): string
    {
        $snapshot_filename = $this->get_snapshot_filename();
        if (!file_exists(dirname($snapshot_filename))) {
            mkdir(dirname($snapshot_filename), 0755, true);
        }
        file_put_contents($snapshot_filename, $snapshot);
        return str_replace(dirname($this->tests_path) . '/', '', $snapshot_filename);
    }
    /**
     * Flushes the snapshots.
     */
    public function flush(): void
    {
        $absolute_snapshots_path = $this->tests_path . '/' . $this->snapshots_path;
        $delete_directory = function (string $path) use (&$delete_directory): void {
            if (file_exists($path)) {
                $scanned_dir = scandir($path);
                assert(is_array($scanned_dir));
                $files = array_diff($scanned_dir, ['.', '..']);
                foreach ($files as $file) {
                    if (is_dir($path . '/' . $file)) {
                        $delete_directory($path . '/' . $file);
                    } else {
                        unlink($path . '/' . $file);
                    }
                }
                rmdir($path);
            }
        };
        if (file_exists($absolute_snapshots_path)) {
            $delete_directory($absolute_snapshots_path);
        }
    }
    /**
     * Gets the snapshot's "filename".
     */
    private function get_snapshot_filename(): string
    {
        $test_file = Test_Suite::get_instance()->get_filename();
        if (str_starts_with($test_file, $this->tests_path)) {
            // if the test file is in the tests directory
            $start_path = $this->tests_path;
        } else {
            // if the test file is in the app, src, etc. directory
            $start_path = $this->root_path;
        }
        // relative path: we use substr() and not str_replace() to remove the start path
        // for instance, if the $startPath is /app/ and the $testFile is /app/app/tests/Unit/ExampleTest.php, we should only remove the first /app/ from the path
        $relative_path = substr($test_file, strlen($start_path));
        // remove extension from filename
        $relative_path = substr($relative_path, 0, (int) strrpos($relative_path, '.'));
        $description = Test_Suite::get_instance()->get_description();
        if ($this->get_current_snapshot_counter() > 1) {
            $description .= '__' . $this->get_current_snapshot_counter();
        }
        return sprintf('%s/%s.snap', $this->tests_path . '/' . $this->snapshots_path . $relative_path, $description);
    }
    private function get_current_snapshot_key(): string
    {
        return Test_Suite::get_instance()->get_filename() . '###' . Test_Suite::get_instance()->get_description();
    }
    private function get_current_snapshot_counter(): int
    {
        return self::$expectations_counter[$this->get_current_snapshot_key()] ?? 0;
    }
    public function start_new_expectation(): void
    {
        $key = $this->get_current_snapshot_key();
        if (!isset(self::$expectations_counter[$key])) {
            self::$expectations_counter[$key] = 0;
        }
        self::$expectations_counter[$key]++;
    }
}