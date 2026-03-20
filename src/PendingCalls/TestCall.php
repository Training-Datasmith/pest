<?php

declare (strict_types=1);
namespace Pest\Pending_Calls;

use Closure;
use Pest\Concerns\Testable;
use Pest\Exceptions\InvalidArgumentException;
use Pest\Exceptions\Test_Description_Missing;
use Pest\Factories\Attribute;
use Pest\Factories\Test_Case_Method_Factory;
use Pest\Mutate\Repositories\Configuration_Repository;
use Pest\Pending_Calls\Concerns\Describable;
use Pest\Plugins\Environment;
use Pest\Plugins\Only;
use Pest\Support\Backtrace;
use Pest\Support\Container;
use Pest\Support\Exporter;
use Pest\Support\Higher_Order_Callables;
use Pest\Support\Null_Closure;
use Pest\Support\Str;
use Pest\Test_Suite;
use Php_Unit\Framework\Assertion_Failed_Error;
use Php_Unit\Framework\Attributes\Covers_Class;
use Php_Unit\Framework\Attributes\Covers_Function;
use Php_Unit\Framework\Attributes\Covers_Trait;
use Php_Unit\Framework\Attributes\Group;
use Php_Unit\Framework\Test_Case;
/**
 * @internal
 *
 * @mixin HigherOrderCallables|TestCase|Testable
 */
final class Test_Call
{
    use Describable;
    /**
     * The list of test case factory attributes.
     *
     * @var array<int, Attribute>
     */
    private array $test_case_factory_attributes = [];
    /**
     * The Test Case Factory.
     */
    public readonly Test_Case_Method_Factory $test_case_method;
    /**
     * If test call is descriptionLess.
     */
    private readonly bool $description_less;
    /**
     * Creates a new Pending Call.
     */
    public function __construct(private readonly Test_Suite $test_suite, private readonly string $filename, private ?string $description = null, ?Closure $closure = null)
    {
        $this->test_case_method = new Test_Case_Method_Factory($filename, $closure);
        $this->description_less = $description === null;
        $this->describing = Describe_Call::describing();
        $this->test_suite->before_each->get($this->filename)[0]($this);
    }
    /**
     * Runs the given closure after the test.
     */
    public function after(Closure $closure): self
    {
        if ($this->description === null) {
            throw new Test_Description_Missing($this->filename);
        }
        $description = $this->describing === [] ? $this->description : Str::describe($this->describing, $this->description);
        $filename = $this->filename;
        $when = function () use ($closure, $filename, $description): void {
            if ($this::$__filename !== $filename) {
                // @phpstan-ignore-line
                return;
            }
            if ($this->__description !== $description) {
                // @phpstan-ignore-line
                return;
            }
            if ($this->__ran !== true) {
                // @phpstan-ignore-line
                return;
            }
            $closure->call($this);
        };
        new After_Each_Call($this->test_suite, $this->filename, $when->bind_to(new \stdClass()));
        return $this;
    }
    /**
     * Asserts that the test fails with the given message.
     */
    public function fails(?string $message = null): self
    {
        return $this->throws(Assertion_Failed_Error::class, $message);
    }
    /**
     * Asserts that the test throws the given `$exceptionClass` when called.
     */
    public function throws(string|int $exception, ?string $exception_message = null, ?int $exception_code = null): self
    {
        if (is_int($exception)) {
            $exception_code = $exception;
        } elseif (class_exists($exception)) {
            $this->test_case_method->proxies->add(Backtrace::file(), Backtrace::line(), 'expectException', [$exception]);
        } else {
            $exception_message = $exception;
        }
        if (is_string($exception_message)) {
            $this->test_case_method->proxies->add(Backtrace::file(), Backtrace::line(), 'expectExceptionMessage', [$exception_message]);
        }
        if (is_int($exception_code)) {
            $this->test_case_method->proxies->add(Backtrace::file(), Backtrace::line(), 'expectExceptionCode', [$exception_code]);
        }
        return $this;
    }
    /**
     * Asserts that the test throws the given `$exceptionClass` when called if the given condition is true.
     *
     * @param  (callable(): bool)|bool  $condition
     */
    public function throws_if(callable|bool $condition, string|int $exception, ?string $exception_message = null, ?int $exception_code = null): self
    {
        $condition = is_callable($condition) ? $condition : static fn(): bool => $condition;
        if ($condition()) {
            return $this->throws($exception, $exception_message, $exception_code);
        }
        return $this;
    }
    /**
     * Asserts that the test throws the given `$exceptionClass` when called if the given condition is false.
     *
     * @param  (callable(): bool)|bool  $condition
     */
    public function throws_unless(callable|bool $condition, string|int $exception, ?string $exception_message = null, ?int $exception_code = null): self
    {
        $condition = is_callable($condition) ? $condition : static fn(): bool => $condition;
        if (!$condition()) {
            return $this->throws($exception, $exception_message, $exception_code);
        }
        return $this;
    }
    /**
     * Runs the current test multiple times with each item of the given `iterable`.
     *
     * @param  Closure|iterable<array-key, mixed>|string  $data
     */
    public function with(Closure|iterable|string ...$data): self
    {
        foreach ($data as $dataset) {
            $this->test_case_method->datasets[] = $dataset;
        }
        return $this;
    }
    /**
     * Sets the test depends.
     */
    public function depends(string ...$depends): self
    {
        foreach ($depends as $depend) {
            $this->test_case_method->depends[] = $depend;
        }
        return $this;
    }
    /**
     * Sets the test group(s).
     */
    public function group(string ...$groups): self
    {
        foreach ($groups as $group) {
            $this->test_case_method->attributes[] = new Attribute(Group::class, [$group]);
        }
        return $this;
    }
    /**
     * Filters the test suite by "only" tests.
     */
    public function only(): self
    {
        Only::enable($this, ...func_get_args());
        return $this;
    }
    /**
     * Skips the current test.
     */
    public function skip(Closure|bool|string $condition_or_message = true, string $message = ''): self
    {
        $condition = is_string($condition_or_message) ? Null_Closure::create() : $condition_or_message;
        $condition = is_callable($condition) ? $condition : fn(): bool => $condition;
        $message = is_string($condition_or_message) ? $condition_or_message : $message;
        /** @var callable(): bool $condition */
        $condition = $condition->bind_to(null);
        $this->test_case_method->chains->add_when($condition, $this->filename, Backtrace::line(), 'markTestSkipped', [$message]);
        return $this;
    }
    /**
     * Skips the current test on the given PHP version.
     */
    public function skip_on_php(string $version): self
    {
        if (mb_strlen($version) < 2) {
            throw new InvalidArgumentException('The version must start with [<] or [>].');
        }
        if (str_starts_with($version, '>=') || str_starts_with($version, '<=')) {
            $operator = substr($version, 0, 2);
            $version = substr($version, 2);
        } elseif (str_starts_with($version, '>') || str_starts_with($version, '<')) {
            $operator = $version[0];
            $version = substr($version, 1);
            // ensure starts with number:
        } elseif (is_numeric($version[0])) {
            $operator = '==';
        } else {
            throw new InvalidArgumentException('The version must start with [<, >, <=, >=] or a number.');
        }
        return $this->skip(version_compare(PHP_VERSION, $version, $operator), sprintf('This test is skipped on PHP [%s%s].', $operator, $version));
    }
    /**
     * Skips the current test if the given test is running on Windows.
     */
    public function skip_on_windows(): self
    {
        return $this->skip_on_os('Windows', 'This test is skipped on [Windows].');
    }
    /**
     * Skips the current test if the given test is running on Mac OS.
     */
    public function skip_on_mac(): self
    {
        return $this->skip_on_os('Darwin', 'This test is skipped on [Mac].');
    }
    /**
     * Skips the current test if the given test is running on Linux.
     */
    public function skip_on_linux(): self
    {
        return $this->skip_on_os('Linux', 'This test is skipped on [Linux].');
    }
    /**
     * Skips the current test if the given test is running on the given operating systems.
     */
    private function skip_on_os(string $os_family, string $message): self
    {
        return $os_family === PHP_OS_FAMILY ? $this->skip($message) : $this;
    }
    /**
     * Weather the current test is running on a CI environment.
     */
    private function running_on_ci(): bool
    {
        foreach (['CI', 'GITHUB_ACTIONS', 'GITLAB_CI', 'CIRCLECI', 'TRAVIS', 'APPVEYOR', 'BITBUCKET_BUILD_NUMBER', 'BUILDKITE', 'TEAMCITY_VERSION', 'JENKINS_URL', 'SYSTEM_COLLECTIONURI', 'CI_NAME', 'TASKCLUSTER_ROOT_URL', 'DRONE', 'WERCKER', 'NEVERCODE', 'SEMAPHORE', 'NETLIFY', 'NOW_BUILDER'] as $env) {
            if (getenv($env) !== false) {
                return true;
            }
        }
        return Environment::name() === Environment::CI;
    }
    /**
     * Skips the current test when running on a CI environments.
     */
    public function skip_on_ci(): self
    {
        if ($this->running_on_ci()) {
            return $this->skip('This test is skipped on [CI].');
        }
        return $this;
    }
    public function skip_locally(): self
    {
        if ($this->running_on_ci() === false) {
            return $this->skip('This test is skipped [locally].');
        }
        return $this;
    }
    /**
     * Skips the current test unless the given test is running on Windows.
     */
    public function only_on_windows(): self
    {
        return $this->skip_on_mac()->skip_on_linux();
    }
    /**
     * Skips the current test unless the given test is running on Mac.
     */
    public function only_on_mac(): self
    {
        return $this->skip_on_windows()->skip_on_linux();
    }
    /**
     * Skips the current test unless the given test is running on Linux.
     */
    public function only_on_linux(): self
    {
        return $this->skip_on_windows()->skip_on_mac();
    }
    /**
     * Repeats the current test the given number of times.
     */
    public function repeat(int $times): self
    {
        if ($times < 1) {
            throw new InvalidArgumentException('The number of repetitions must be greater than 0.');
        }
        $this->test_case_method->repetitions = $times;
        return $this;
    }
    /**
     * Marks the test as "todo".
     */
    public function todo(
        // @phpstan-ignore-line
        array|string|null $note = null,
        array|string|null $assignee = null,
        array|string|int|null $issue = null,
        array|string|int|null $pr = null
    ): self
    {
        $this->skip('__TODO__');
        $this->test_case_method->todo = true;
        if ($issue !== null) {
            $this->issue($issue);
        }
        if ($pr !== null) {
            $this->pr($pr);
        }
        if ($assignee !== null) {
            $this->assignee($assignee);
        }
        if ($note !== null) {
            $this->note($note);
        }
        return $this;
    }
    /**
     * Sets the test as "work in progress".
     */
    public function wip(
        // @phpstan-ignore-line
        array|string|null $note = null,
        array|string|null $assignee = null,
        array|string|int|null $issue = null,
        array|string|int|null $pr = null
    ): self
    {
        if ($issue !== null) {
            $this->issue($issue);
        }
        if ($pr !== null) {
            $this->pr($pr);
        }
        if ($assignee !== null) {
            $this->assignee($assignee);
        }
        if ($note !== null) {
            $this->note($note);
        }
        return $this;
    }
    /**
     * Sets the test as "done".
     */
    public function done(
        // @phpstan-ignore-line
        array|string|null $note = null,
        array|string|null $assignee = null,
        array|string|int|null $issue = null,
        array|string|int|null $pr = null
    ): self
    {
        if ($issue !== null) {
            $this->issue($issue);
        }
        if ($pr !== null) {
            $this->pr($pr);
        }
        if ($assignee !== null) {
            $this->assignee($assignee);
        }
        if ($note !== null) {
            $this->note($note);
        }
        return $this;
    }
    /**
     * Associates the test with the given issue(s).
     *
     * @param  array<int, string|int>|string|int  $number
     */
    public function issue(array|string|int $number): self
    {
        $number = is_array($number) ? $number : [$number];
        $number = array_map(fn(string|int $number): int => (int) ltrim((string) $number, '#'), $number);
        $this->test_case_method->issues = array_merge($this->test_case_method->issues, $number);
        return $this;
    }
    /**
     * Associates the test with the given ticket(s). (Alias for `issue`)
     *
     * @param  array<int, string|int>|string|int  $number
     */
    public function ticket(array|string|int $number): self
    {
        return $this->issue($number);
    }
    /**
     * Sets the test assignee(s).
     *
     * @param  array<int, string>|string  $assignee
     */
    public function assignee(array|string $assignee): self
    {
        $assignees = is_array($assignee) ? $assignee : [$assignee];
        $this->test_case_method->assignees = array_unique(array_merge($this->test_case_method->assignees, $assignees));
        return $this;
    }
    /**
     * Associates the test with the given pull request(s).
     *
     * @param  array<int, string|int>|string|int  $number
     */
    public function pr(array|string|int $number): self
    {
        $number = is_array($number) ? $number : [$number];
        $number = array_map(fn(string|int $number): int => (int) ltrim((string) $number, '#'), $number);
        $this->test_case_method->prs = array_unique(array_merge($this->test_case_method->prs, $number));
        return $this;
    }
    /**
     * Adds a note to the test.
     *
     * @param  array<int, string>|string  $note
     */
    public function note(array|string $note): self
    {
        $notes = is_array($note) ? $note : [$note];
        $this->test_case_method->notes = array_unique(array_merge($this->test_case_method->notes, $notes));
        return $this;
    }
    /**
     * Sets the covered classes or methods.
     *
     * @param  array<int, string>|string  $classesOrFunctions
     */
    public function covers(array|string ...$classes_or_functions): self
    {
        /** @var array<int, string> $classesOrFunctions */
        $classes_or_functions = array_reduce($classes_or_functions, fn($carry, $item): array => is_array($item) ? array_merge($carry, $item) : array_merge($carry, [$item]), []);
        // @pest-ignore-type
        foreach ($classes_or_functions as $class_or_function) {
            $is_class = class_exists($class_or_function) || interface_exists($class_or_function) || enum_exists($class_or_function);
            $is_trait = trait_exists($class_or_function);
            $is_function = function_exists($class_or_function);
            if (!$is_class && !$is_trait && !$is_function) {
                throw new InvalidArgumentException(sprintf('No class, trait or method named "%s" has been found.', $class_or_function));
            }
            if ($is_class) {
                $this->covers_class($class_or_function);
            } elseif ($is_trait) {
                $this->covers_trait($class_or_function);
            } else {
                $this->covers_function($class_or_function);
            }
        }
        return $this;
    }
    /**
     * Sets the covered classes.
     */
    public function covers_class(string ...$classes): self
    {
        foreach ($classes as $class) {
            $this->test_case_factory_attributes[] = new Attribute(Covers_Class::class, [$class]);
        }
        /** @var ConfigurationRepository $configurationRepository */
        $configuration_repository = Container::get_instance()->get(Configuration_Repository::class);
        $paths = $configuration_repository->cli_configuration->to_array()['paths'] ?? false;
        if (!is_array($paths)) {
            $configuration_repository->global_configuration('default')->class(...$classes);
            // @phpstan-ignore-line
        }
        return $this;
    }
    /**
     * Sets the covered classes.
     */
    public function covers_trait(string ...$traits): self
    {
        foreach ($traits as $trait) {
            $this->test_case_factory_attributes[] = new Attribute(Covers_Trait::class, [$trait]);
        }
        /** @var ConfigurationRepository $configurationRepository */
        $configuration_repository = Container::get_instance()->get(Configuration_Repository::class);
        $paths = $configuration_repository->cli_configuration->to_array()['paths'] ?? false;
        if (!is_array($paths)) {
            $configuration_repository->global_configuration('default')->class(...$traits);
            // @phpstan-ignore-line
        }
        return $this;
    }
    /**
     * Sets the covered functions.
     */
    public function covers_function(string ...$functions): self
    {
        foreach ($functions as $function) {
            $this->test_case_factory_attributes[] = new Attribute(Covers_Function::class, [$function]);
        }
        return $this;
    }
    /**
     * Adds one or more references to the tested method or class. This helps
     * to link test cases to the source code for easier navigation.
     *
     * @param  array<class-string|string>|class-string  ...$classes
     */
    public function references(string|array ...$classes): self
    {
        assert($classes !== []);
        return $this;
    }
    /**
     * Adds one or more references to the tested method or class. This helps
     * to link test cases to the source code for easier navigation.
     *
     * @param  array<class-string|string>|class-string  ...$classes
     */
    public function see(string|array ...$classes): self
    {
        return $this->references(...$classes);
    }
    /**
     * Informs the test runner that no expectations happen in this test,
     * and its purpose is simply to check whether the given code can
     * be executed without throwing exceptions.
     */
    public function throws_no_exceptions(): self
    {
        $this->test_case_method->proxies->add(Backtrace::file(), Backtrace::line(), 'expectNotToPerformAssertions', []);
        return $this;
    }
    /**
     * Saves the property accessors to be used on the target.
     */
    public function __get(string $name): self
    {
        return $this->add_chain(Backtrace::file(), Backtrace::line(), $name);
    }
    /**
     * Saves the calls to be used on the target.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): self
    {
        return $this->add_chain(Backtrace::file(), Backtrace::line(), $name, $arguments);
    }
    /**
     * Add a chain to the test case factory. Omitting the arguments will treat it as a property accessor.
     *
     * @param  array<int, mixed>|null  $arguments
     */
    private function add_chain(string $file, int $line, string $name, ?array $arguments = null): self
    {
        $exporter = Exporter::default();
        $this->test_case_method->chains->add($file, $line, $name, $arguments);
        if ($this->description_less) {
            Exporter::default();
            if ($this->description !== null) {
                $this->description .= ' → ';
            }
            $this->description .= $arguments === null ? $name : sprintf('%s %s', $name, $exporter->shortened_recursive_export($arguments));
        }
        return $this;
    }
    /**
     * Creates the Call.
     */
    public function __destruct()
    {
        if ($this->description === null) {
            throw new Test_Description_Missing($this->filename);
        }
        if ($this->describing !== []) {
            $this->test_case_method->describing = $this->describing;
            $this->test_case_method->description = Str::describe($this->describing, $this->description);
        } else {
            $this->test_case_method->description = $this->description;
        }
        $this->test_suite->tests->set($this->test_case_method);
        if (!is_null($test_case = $this->test_suite->tests->get($this->filename))) {
            $attributes_to_merge = array_filter($this->test_case_factory_attributes, fn(Attribute $attribute_to_merge): bool => array_filter($test_case->attributes, fn(Attribute $attribute): bool => serialize($attribute_to_merge) === serialize($attribute)) === []);
            $test_case->attributes = array_merge($test_case->attributes, $attributes_to_merge);
        }
    }
}