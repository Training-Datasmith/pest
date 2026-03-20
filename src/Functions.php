<?php

declare (strict_types=1);
use Pest\Browser\Api\Arrayable_Pending_Awaitable_Page;
use Pest\Browser\Api\Pending_Awaitable_Page;
use Pest\Concerns\Expectable;
use Pest\Configuration;
use Pest\Exceptions\After_All_Within_Describe;
use Pest\Exceptions\Before_All_Within_Describe;
use Pest\Expectation;
use Pest\Installers\Plugin_Browser;
use Pest\Mutate\Contracts\Mutation_Test_Runner;
use Pest\Mutate\Repositories\Configuration_Repository;
use Pest\Pending_Calls\After_Each_Call;
use Pest\Pending_Calls\Before_Each_Call;
use Pest\Pending_Calls\Describe_Call;
use Pest\Pending_Calls\Test_Call;
use Pest\Pending_Calls\Uses_Call;
use Pest\Repositories\Datasets_Repository;
use Pest\Support\Backtrace;
use Pest\Support\Container;
use Pest\Support\Dataset_Info;
use Pest\Support\Description;
use Pest\Support\Higher_Order_Tap_Proxy;
use Pest\Test_Suite;
use Php_Unit\Framework\Test_Case;
if (!function_exists('expect')) {
    /**
     * Creates a new expectation.
     *
     * @template TValue
     *
     * @param  TValue|null  $value
     * @return Expectation<TValue|null>
     */
    function expect(mixed $value = null): Expectation
    {
        return new Expectation($value);
    }
}
if (!function_exists('beforeAll')) {
    /**
     * Runs the given closure before all tests in the current file.
     */
    function before_all(Closure $closure): void
    {
        if (Describe_Call::describing() !== []) {
            $filename = Backtrace::file();
            throw new Before_All_Within_Describe($filename);
        }
        Test_Suite::get_instance()->before_all->set($closure);
    }
}
if (!function_exists('beforeEach')) {
    /**
     * Runs the given closure before each test in the current file.
     *
     * @param-closure-this TestCase  $closure
     *
     * @return HigherOrderTapProxy<Expectable|TestCall|TestCase>|Expectable|TestCall|TestCase|mixed
     */
    function before_each(?Closure $closure = null): Before_Each_Call
    {
        $filename = Backtrace::file();
        return new Before_Each_Call(Test_Suite::get_instance(), $filename, $closure);
    }
}
if (!function_exists('dataset')) {
    /**
     * Registers the given dataset.
     *
     * @param  Closure|iterable<int|string, mixed>  $dataset
     */
    function dataset(string $name, Closure|iterable $dataset): void
    {
        $scope = Dataset_Info::scope(Backtrace::datasets_file());
        Datasets_Repository::set($name, $dataset, $scope);
    }
}
if (!function_exists('describe')) {
    /**
     * Adds the given closure as a group of tests. The first argument
     * is the group description; the second argument is a closure
     * that contains the group tests.
     *
     * @return HigherOrderTapProxy<Expectable|TestCall|TestCase>|Expectable|TestCall|TestCase|mixed
     */
    function describe(string $description, Closure $tests): Describe_Call
    {
        $filename = Backtrace::test_file();
        return new Describe_Call(Test_Suite::get_instance(), $filename, new Description($description), $tests);
    }
}
if (!function_exists('uses')) {
    /**
     * The uses function binds the given
     * arguments to test closures.
     *
     * @param  class-string  ...$classAndTraits
     */
    function uses(string ...$class_and_traits): Uses_Call
    {
        $filename = Backtrace::file();
        return new Uses_Call($filename, array_values($class_and_traits));
    }
}
if (!function_exists('pest')) {
    /**
     * Creates a new Pest configuration instance.
     */
    function pest(): Configuration
    {
        return new Configuration(Backtrace::file());
    }
}
if (!function_exists('test')) {
    /**
     * Adds the given closure as a test. The first argument
     * is the test description; the second argument is
     * a closure that contains the test expectations.
     *
     * @param-closure-this TestCase  $closure
     *
     * @return Expectable|TestCall|TestCase|mixed
     */
    function test(?string $description = null, ?Closure $closure = null): Higher_Order_Tap_Proxy|Test_Call
    {
        if ($description === null && Test_Suite::get_instance()->test instanceof Test_Case) {
            return new Higher_Order_Tap_Proxy(Test_Suite::get_instance()->test);
        }
        $filename = Backtrace::test_file();
        return new Test_Call(Test_Suite::get_instance(), $filename, $description, $closure);
    }
}
if (!function_exists('it')) {
    /**
     * Adds the given closure as a test. The first argument
     * is the test description; the second argument is
     * a closure that contains the test expectations.
     *
     * @param-closure-this TestCase  $closure
     *
     * @return Expectable|TestCall|TestCase|mixed
     */
    function it(string $description, ?Closure $closure = null): Test_Call
    {
        $description = sprintf('it %s', $description);
        /** @var TestCall $test */
        $test = test($description, $closure);
        return $test;
    }
}
if (!function_exists('todo')) {
    /**
     * Creates a new test that is marked as "todo".
     *
     * @return Expectable|TestCall|TestCase|mixed
     */
    function todo(string $description): Test_Call
    {
        $test = test($description);
        assert($test instanceof Test_Call);
        return $test->todo();
    }
}
if (!function_exists('afterEach')) {
    /**
     * Runs the given closure after each test in the current file.
     *
     * @param-closure-this TestCase  $closure
     *
     * @return Expectable|HigherOrderTapProxy<Expectable|TestCall|TestCase>|TestCall|mixed
     */
    function after_each(?Closure $closure = null): After_Each_Call
    {
        $filename = Backtrace::file();
        return new After_Each_Call(Test_Suite::get_instance(), $filename, $closure);
    }
}
if (!function_exists('afterAll')) {
    /**
     * Runs the given closure after all tests in the current file.
     */
    function after_all(Closure $closure): void
    {
        if (Describe_Call::describing() !== []) {
            $filename = Backtrace::file();
            throw new After_All_Within_Describe($filename);
        }
        Test_Suite::get_instance()->after_all->set($closure);
    }
}
if (!function_exists('covers')) {
    /**
     * Specifies which classes, or functions, a test case covers.
     *
     * @param  array<int, string>|string  $classesOrFunctions
     */
    function covers(array|string ...$classes_or_functions): void
    {
        $filename = Backtrace::file();
        $before_each_call = new Before_Each_Call(Test_Suite::get_instance(), $filename);
        $before_each_call->covers(...$classes_or_functions);
        $before_each_call->group('__pest_mutate_only');
        /** @var MutationTestRunner $runner */
        $runner = Container::get_instance()->get(Mutation_Test_Runner::class);
        /** @var ConfigurationRepository $configurationRepository */
        $configuration_repository = Container::get_instance()->get(Configuration_Repository::class);
        $everything = $configuration_repository->cli_configuration->to_array()['everything'] ?? false;
        $classes = $configuration_repository->cli_configuration->to_array()['classes'] ?? false;
        $paths = $configuration_repository->cli_configuration->to_array()['paths'] ?? false;
        if ($runner->is_enabled() && !$everything && !is_array($classes) && !is_array($paths)) {
            $before_each_call->only('__pest_mutate_only');
        }
    }
}
if (!function_exists('mutates')) {
    /**
     * Specifies which classes, enums, or traits a test case mutates.
     *
     * @param  array<int, string>|string  $targets
     */
    function mutates(array|string ...$targets): void
    {
        $filename = Backtrace::file();
        $before_each_call = new Before_Each_Call(Test_Suite::get_instance(), $filename);
        $before_each_call->group('__pest_mutate_only');
        /** @var MutationTestRunner $runner */
        $runner = Container::get_instance()->get(Mutation_Test_Runner::class);
        /** @var ConfigurationRepository $configurationRepository */
        $configuration_repository = Container::get_instance()->get(Configuration_Repository::class);
        $everything = $configuration_repository->cli_configuration->to_array()['everything'] ?? false;
        $classes = $configuration_repository->cli_configuration->to_array()['classes'] ?? false;
        $paths = $configuration_repository->cli_configuration->to_array()['paths'] ?? false;
        if ($runner->is_enabled() && !$everything && !is_array($classes) && !is_array($paths)) {
            $before_each_call->only('__pest_mutate_only');
        }
        /** @var ConfigurationRepository $configurationRepository */
        $configuration_repository = Container::get_instance()->get(Configuration_Repository::class);
        $paths = $configuration_repository->cli_configuration->to_array()['paths'] ?? false;
        if (!is_array($paths)) {
            $configuration_repository->global_configuration('default')->class(...$targets);
            // @phpstan-ignore-line
        }
    }
}
if (!function_exists('fixture')) {
    /**
     * Returns the absolute path to a fixture file.
     */
    function fixture(string $file): string
    {
        $file = implode(DIRECTORY_SEPARATOR, [Test_Suite::get_instance()->root_path, Test_Suite::get_instance()->test_path, 'Fixtures', str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file)]);
        $file_real_path = realpath($file);
        if ($file_real_path === false) {
            throw new InvalidArgumentException('The fixture file [' . $file . '] does not exist.');
        }
        return $file_real_path;
    }
}
if (!function_exists('visit')) {
    /**
     * Browse to the given URL.
     *
     * @template TUrl of array<int, string>|string
     *
     * @param  TUrl  $url
     * @param  array<string, mixed>  $options
     * @return (TUrl is array<int, string> ? ArrayablePendingAwaitablePage : PendingAwaitablePage)
     */
    function visit(array|string $url, array $options = []): Arrayable_Pending_Awaitable_Page|Pending_Awaitable_Page
    {
        if (!class_exists(Pest\Browser\Configuration::class)) {
            Plugin_Browser::install();
            exit(0);
        }
        // @phpstan-ignore-next-line
        return test()->visit($url, $options);
    }
}