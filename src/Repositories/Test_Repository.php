<?php

declare (strict_types=1);
namespace Pest\Repositories;

use Closure;
use Pest\Contracts\Test_Case_Filter;
use Pest\Contracts\Test_Case_Method_Filter;
use Pest\Exceptions\Test_Case_Already_In_Use;
use Pest\Exceptions\Test_Case_Class_Or_Trait_Not_Found;
use Pest\Factories\Attribute;
use Pest\Factories\Test_Case_Factory;
use Pest\Factories\Test_Case_Method_Factory;
use Pest\Support\Str;
use Php_Unit\Framework\Attributes\Group;
use Php_Unit\Framework\Test_Case;
/**
 * @internal
 */
final class Test_Repository
{
    /**
     * @var array<string, TestCaseFactory>
     */
    private array $test_cases = [];
    /**
     * @var array<string, array{0: array<int, string>, 1: array<int, string>, 2: array<int, array<int, string|Closure>>}>
     */
    private array $uses = [];
    /**
     * @var array<int, TestCaseFilter>
     */
    private array $test_case_filters = [];
    /**
     * @var array<int, TestCaseMethodFilter>
     */
    private array $test_case_method_filters = [];
    /**
     * Counts the number of test cases.
     */
    public function count(): int
    {
        return count($this->test_cases);
    }
    /**
     * Returns the filename of each test that should be executed in the suite.
     *
     * @return array<int, string>
     */
    public function get_filenames(): array
    {
        return array_values(array_map(static fn(Test_Case_Factory $factory): string => $factory->filename, $this->test_cases));
    }
    /**
     * Uses the given `$testCaseClass` on the given `$paths`.
     *
     * @param  array<int, string>  $classOrTraits
     * @param  array<int, string>  $groups
     * @param  array<int, string>  $paths
     * @param  array<int, Closure>  $hooks
     */
    public function use(array $class_or_traits, array $groups, array $paths, array $hooks): void
    {
        foreach ($class_or_traits as $class_or_trait) {
            if (class_exists($class_or_trait)) {
                continue;
            }
            if (trait_exists($class_or_trait)) {
                continue;
            }
            throw new Test_Case_Class_Or_Trait_Not_Found($class_or_trait);
        }
        $hooks = array_map(fn(Closure $hook): array => [$hook], $hooks);
        foreach ($paths as $path) {
            if (array_key_exists($path, $this->uses)) {
                $this->uses[$path] = [[...$this->uses[$path][0], ...$class_or_traits], [...$this->uses[$path][1], ...$groups], array_map(fn(int $index): array => [...$this->uses[$path][2][$index] ?? [], ...$hooks[$index] ?? []], range(0, 3))];
            } else {
                $this->uses[$path] = [$class_or_traits, $groups, $hooks];
            }
        }
    }
    /**
     * Filters the test cases using the given filter.
     */
    public function add_test_case_filter(Test_Case_Filter $filter): void
    {
        $this->test_case_filters[] = $filter;
    }
    /**
     * Filters the test cases using the given filter.
     */
    public function add_test_case_method_filter(Test_Case_Method_Filter $filter): void
    {
        $this->test_case_method_filters[] = $filter;
    }
    /**
     * Gets the test case factory from the given filename.
     */
    public function get(string $filename): ?Test_Case_Factory
    {
        return $this->test_cases[$filename] ?? null;
    }
    /**
     * Sets a new test case method.
     */
    public function set(Test_Case_Method_Factory $method): void
    {
        foreach ($this->test_case_filters as $filter) {
            if (!$filter->accept($method->filename)) {
                return;
            }
        }
        foreach ($this->test_case_method_filters as $filter) {
            if (!$filter->accept($method)) {
                return;
            }
        }
        if (!array_key_exists($method->filename, $this->test_cases)) {
            $this->test_cases[$method->filename] = new Test_Case_Factory($method->filename);
        }
        $this->test_cases[$method->filename]->add_method($method);
    }
    /**
     * Makes a Test Case from the given filename, if exists.
     */
    public function make_if_needed(string $filename): void
    {
        if (!array_key_exists($filename, $this->test_cases)) {
            return;
        }
        foreach ($this->test_case_filters as $filter) {
            if (!$filter->accept($filename)) {
                return;
            }
        }
        $this->make($this->test_cases[$filename]);
    }
    /**
     * Makes a Test Case using the given factory.
     */
    private function make(Test_Case_Factory $test_case): void
    {
        $starts_with = static fn(string $target, string $directory): bool => Str::starts_with($target, $directory . DIRECTORY_SEPARATOR);
        foreach ($this->uses as $path => $uses) {
            [$class_or_traits, $groups, $hooks] = $uses;
            if (!is_dir($path) && $test_case->filename === $path || is_dir($path) && $starts_with($test_case->filename, $path)) {
                foreach ($class_or_traits as $class) {
                    /** @var string $class */
                    if (class_exists($class)) {
                        if ($test_case->class !== Test_Case::class) {
                            throw new Test_Case_Already_In_Use($test_case->class, $class, $test_case->filename);
                        }
                        $test_case->class = $class;
                    } elseif (trait_exists($class)) {
                        $test_case->traits[] = $class;
                    }
                }
                foreach ($test_case->methods as $method) {
                    foreach ($groups as $group) {
                        $method->attributes[] = new Attribute(Group::class, [$group]);
                    }
                }
                foreach ($test_case->methods as $method) {
                    $method->groups = [...$groups, ...$method->groups];
                }
                foreach (['__addBeforeAll', '__addBeforeEach', '__addAfterEach', '__addAfterAll'] as $index => $name) {
                    foreach ($hooks[$index] ?? [null] as $hook) {
                        $test_case->factory_proxies->add($test_case->filename, 0, $name, [$hook]);
                    }
                }
            }
        }
        $test_case->make();
    }
}