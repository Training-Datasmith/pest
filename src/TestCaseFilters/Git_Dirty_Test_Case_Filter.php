<?php

declare (strict_types=1);
namespace Pest\Test_Case_Filters;

use Pest\Contracts\Test_Case_Filter;
use Pest\Exceptions\Missing_Dependency;
use Pest\Exceptions\No_Dirty_Tests_Found;
use Pest\Panic;
use Pest\Test_Suite;
use Symfony\Component\Process\Process;
final class Git_Dirty_Test_Case_Filter implements Test_Case_Filter
{
    /**
     * @var array<int, string>|null
     */
    private ?array $changed_files = null;
    /**
     * Creates a new instance of the filter.
     */
    public function __construct(private readonly string $project_root)
    {
        // ...
    }
    /**
     * {@inheritdoc}
     */
    public function accept(string $test_case_filename): bool
    {
        if ($this->changed_files === null) {
            $this->load_changed_files();
        }
        assert(is_array($this->changed_files));
        $relative_path = str_replace($this->project_root, '', $test_case_filename);
        $relative_path = str_replace(DIRECTORY_SEPARATOR, '/', $relative_path);
        if (str_starts_with($relative_path, '/')) {
            $relative_path = substr($relative_path, 1);
        }
        return in_array($relative_path, $this->changed_files, true);
    }
    /**
     * Loads the changed files.
     */
    private function load_changed_files(): void
    {
        $process = new Process(['git', 'status', '--short', '--', '*.php']);
        $process->run();
        if (!$process->is_successful()) {
            throw new Missing_Dependency('Filter by dirty files', 'git');
        }
        $output = preg_split('/\R+/', $process->get_output(), flags: PREG_SPLIT_NO_EMPTY);
        assert(is_array($output));
        $dirty_files = [];
        foreach ($output as $dirty_file) {
            $dirty_files[substr($dirty_file, 3)] = trim(substr($dirty_file, 0, 3));
        }
        $dirty_files = array_filter($dirty_files, fn(string $status): bool => $status !== 'D');
        $dirty_files = array_map(fn(string $file, string $status): string => in_array($status, ['R', 'RM'], true) ? explode(' -> ', $file)[1] : $file, array_keys($dirty_files), $dirty_files);
        $dirty_files = array_filter($dirty_files, fn(string $file): bool => str_starts_with('.' . DIRECTORY_SEPARATOR . $file, Test_Suite::get_instance()->test_path) || str_starts_with($file, Test_Suite::get_instance()->test_path));
        $dirty_files = array_values($dirty_files);
        if ($dirty_files === []) {
            Panic::with(new No_Dirty_Tests_Found());
        }
        $this->changed_files = $dirty_files;
    }
}