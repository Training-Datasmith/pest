<?php

declare (strict_types=1);
namespace Pest\Concerns;

use Closure;
use Pest\Exceptions\Dataset_Arguments_Mismatch;
use Pest\Panic;
use Pest\Preset;
use Pest\Support\Chainable_Closure;
use Pest\Support\Exception_Trace;
use Pest\Support\Reflection;
use Pest\Support\Shell;
use Pest\Test_Suite;
use Php_Unit\Framework\Attributes\Post_Condition;
use Php_Unit\Framework\Test_Case;
use Reflection_Exception;
use ReflectionFunction;
use ReflectionParameter;
use Throwable;
/**
 * @internal
 *
 * @mixin TestCase
 */
trait Testable
{
    /**
     * The test's description.
     */
    private string $__description;
    /**
     * The test's latest description.
     */
    private static string $__latest_description;
    /**
     * The test's assignees.
     */
    private static array $__latest_assignees = [];
    /**
     * The test's notes.
     */
    private static array $__latest_notes = [];
    /**
     * The test's issues.
     *
     * @var array<int, int>
     */
    private static array $__latest_issues = [];
    /**
     * The test's PRs.
     *
     * @var array<int, int>
     */
    private static array $__latest_prs = [];
    /**
     * The test's describing, if any.
     *
     * @var array<int, string>
     */
    public array $__describing = [];
    /**
     * Whether the test has ran or not.
     */
    public bool $__ran = false;
    /**
     * The test's test closure.
     */
    private Closure $__test;
    /**
     * The test's before each closure.
     */
    private ?Closure $__before_each = null;
    /**
     * The test's after each closure.
     */
    private ?Closure $__after_each = null;
    /**
     * The test's before all closure.
     */
    private static ?Closure $__before_all = null;
    /**
     * The test's after all closure.
     */
    private static ?Closure $__after_all = null;
    /**
     * The list of snapshot changes, if any.
     */
    private array $__snapshot_changes = [];
    /**
     * Resets the test case static properties.
     */
    public static function flush(): void
    {
        self::$__before_all = null;
        self::$__after_all = null;
    }
    /**
     * Adds a new "note" to the Test Case.
     */
    public function note(array|string $note): self
    {
        $note = is_array($note) ? $note : [$note];
        self::$__latest_notes = array_merge(self::$__latest_notes, $note);
        return $this;
    }
    /**
     * Adds a new "setUpBeforeClass" to the Test Case.
     */
    public function __add_before_all(?Closure $hook): void
    {
        if (!$hook instanceof Closure) {
            return;
        }
        self::$__before_all = self::$__before_all instanceof Closure ? Chainable_Closure::bound_statically(self::$__before_all, $hook) : $hook;
    }
    /**
     * Adds a new "tearDownAfterClass" to the Test Case.
     */
    public function __add_after_all(?Closure $hook): void
    {
        if (!$hook instanceof Closure) {
            return;
        }
        self::$__after_all = self::$__after_all instanceof Closure ? Chainable_Closure::bound_statically(self::$__after_all, $hook) : $hook;
    }
    /**
     * Adds a new "setUp" to the Test Case.
     */
    public function __add_before_each(?Closure $hook): void
    {
        $this->__add_hook('__beforeEach', $hook);
    }
    /**
     * Adds a new "tearDown" to the Test Case.
     */
    public function __add_after_each(?Closure $hook): void
    {
        $this->__add_hook('__afterEach', $hook);
    }
    /**
     * Adds a new "hook" to the Test Case.
     */
    private function __add_hook(string $property, ?Closure $hook): void
    {
        if (!$hook instanceof Closure) {
            return;
        }
        $this->{$property} = $this->{$property} instanceof Closure ? Chainable_Closure::bound($this->{$property}, $hook) : $hook;
    }
    /**
     * This method is called before the first test of this Test Case is run.
     */
    public static function set_up_before_class(): void
    {
        parent::set_up_before_class();
        $before_all = Test_Suite::get_instance()->before_all->get(self::$__filename);
        if (self::$__before_all instanceof Closure) {
            $before_all = Chainable_Closure::bound_statically(self::$__before_all, $before_all);
        }
        try {
            call_user_func(Closure::bind($before_all, null, self::class));
        } catch (Throwable $e) {
            Panic::with($e);
        }
    }
    /**
     * This method is called after the last test of this Test Case is run.
     */
    public static function tear_down_after_class(): void
    {
        $after_all = Test_Suite::get_instance()->after_all->get(self::$__filename);
        if (self::$__after_all instanceof Closure) {
            $after_all = Chainable_Closure::bound_statically(self::$__after_all, $after_all);
        }
        call_user_func(Closure::bind($after_all, null, self::class));
        parent::tear_down_after_class();
    }
    /**
     * Gets executed before the Test Case.
     */
    protected function set_up(...$arguments): void
    {
        Test_Suite::get_instance()->test = $this;
        $method = Test_Suite::get_instance()->tests->get(self::$__filename)->get_method($this->name());
        $description = $method->description;
        if ($this->data_name()) {
            $description = str_contains((string) $description, ':dataset') ? str_replace(':dataset', str_replace('dataset ', '', $this->data_name()), (string) $description) : $description . ' with ' . $this->data_name();
        }
        $description = htmlspecialchars(html_entity_decode((string) $description), ENT_NOQUOTES);
        if ($method->repetitions > 1) {
            $matches = [];
            preg_match('/\((.*?)\)/', $description, $matches);
            if (count($matches) > 1) {
                if (str_contains($description, 'with ' . $matches[0] . ' /')) {
                    $description = str_replace('with ' . $matches[0] . ' /', '', $description);
                } else {
                    $description = str_replace('with ' . $matches[0], '', $description);
                }
            }
            $description .= ' @ repetition ' . ($matches[1] . ' of ' . $method->repetitions);
        }
        $this->__description = self::$__latest_description = $description;
        self::$__latest_assignees = $method->assignees;
        self::$__latest_notes = $method->notes;
        self::$__latest_issues = $method->issues;
        self::$__latest_prs = $method->prs;
        parent::set_up();
        $before_each = Test_Suite::get_instance()->before_each->get(self::$__filename)[1];
        if ($this->__before_each instanceof Closure) {
            $before_each = Chainable_Closure::bound($this->__before_each, $before_each);
        }
        $this->__call_closure($before_each, $arguments);
    }
    /**
     * Initialize test case properties from TestSuite.
     */
    public function __initialize_test_case(): void
    {
        // Return if the test case has already been initialized
        if (isset($this->__test)) {
            return;
        }
        $name = $this->name();
        $test = Test_Suite::get_instance()->tests->get(self::$__filename);
        if ($test->has_method($name)) {
            $method = $test->get_method($name);
            $this->__description = self::$__latest_description = $method->description;
            self::$__latest_assignees = $method->assignees;
            self::$__latest_notes = $method->notes;
            self::$__latest_issues = $method->issues;
            self::$__latest_prs = $method->prs;
            $this->__describing = $method->describing;
            $this->__test = $method->get_closure();
            $method->set_up($this);
        }
    }
    /**
     * Gets executed after the Test Case.
     */
    protected function tear_down(...$arguments): void
    {
        $after_each = Test_Suite::get_instance()->after_each->get(self::$__filename);
        if ($this->__after_each instanceof Closure) {
            $after_each = Chainable_Closure::bound($this->__after_each, $after_each);
        }
        try {
            $this->__call_closure($after_each, func_get_args());
        } finally {
            parent::tear_down();
            Test_Suite::get_instance()->test = null;
            $method = Test_Suite::get_instance()->tests->get(self::$__filename)->get_method($this->name());
            $method->tear_down($this);
        }
    }
    /**
     * Executes the Test Case current test.
     *
     * @throws Throwable
     */
    private function __run_test(Closure $closure, ...$args): mixed
    {
        $arguments = $this->__resolve_test_arguments($args);
        $this->__ensure_dataset_argument_name_and_number_matches($arguments);
        return $this->__call_closure($closure, $arguments);
    }
    /**
     * Resolve the passed arguments. Any Closures will be bound to the testcase and resolved.
     *
     * @throws Throwable
     */
    private function __resolve_test_arguments(array $arguments): array
    {
        $method = Test_Suite::get_instance()->tests->get(self::$__filename)->get_method($this->name());
        if ($method->repetitions > 1) {
            // If the test is repeated, the first argument is the iteration number
            // we need to move it to the end of the arguments list
            // so that the datasets are the first n arguments
            // and the iteration number is the last argument
            $first_argument = array_shift($arguments);
            $arguments[] = $first_argument;
        }
        $underlying_test = Reflection::get_function_variable($this->__test, 'closure');
        $test_parameter_types = array_values(Reflection::get_function_arguments($underlying_test));
        if (count($arguments) !== 1) {
            foreach ($arguments as $argument_index => $argument_value) {
                if (!$argument_value instanceof Closure) {
                    continue;
                }
                if (in_array($test_parameter_types[$argument_index], [Closure::class, 'callable', 'mixed'])) {
                    continue;
                }
                $arguments[$argument_index] = $this->__call_closure($argument_value, []);
            }
            return $arguments;
        }
        if (!isset($arguments[0]) || !$arguments[0] instanceof Closure) {
            return $arguments;
        }
        if (isset($test_parameter_types[0]) && in_array($test_parameter_types[0], [Closure::class, 'callable'])) {
            return $arguments;
        }
        $bound_dataset_result = $this->__call_closure($arguments[0], []);
        if (count($test_parameter_types) === 1) {
            return [$bound_dataset_result];
        }
        if (!is_array($bound_dataset_result)) {
            return [$bound_dataset_result];
        }
        return array_values($bound_dataset_result);
    }
    /**
     * Ensures dataset items count matches underlying test case required parameters
     *
     * @throws ReflectionException
     * @throws DatasetArgumentsMismatch
     */
    private function __ensure_dataset_argument_name_and_number_matches(array $arguments): void
    {
        if ($arguments === []) {
            return;
        }
        $underlying_test = Reflection::get_function_variable($this->__test, 'closure');
        $test_reflection = new ReflectionFunction($underlying_test);
        $required_parameters_count = $test_reflection->get_number_of_required_parameters();
        $supplied_parameters_count = count($arguments);
        $dataset_parameter_names = array_keys($arguments);
        $test_parameter_names = array_map(fn(ReflectionParameter $reflection_parameter): string => $reflection_parameter->get_name(), array_filter($test_reflection->get_parameters(), fn(ReflectionParameter $reflection_parameter): bool => !$reflection_parameter->is_optional()));
        if (array_diff($test_parameter_names, $dataset_parameter_names) === []) {
            return;
        }
        if (isset($test_parameter_names[0]) && $supplied_parameters_count >= $required_parameters_count) {
            return;
        }
        throw new Dataset_Arguments_Mismatch($required_parameters_count, $supplied_parameters_count);
    }
    /**
     * @throws Throwable
     */
    private function __call_closure(Closure $closure, array $arguments): mixed
    {
        return Exception_Trace::ensure(fn(): mixed => call_user_func_array(Closure::bind($closure, $this, $this::class), $arguments));
    }
    /**
     * Uses the given preset on the test.
     */
    public function preset(): Preset
    {
        return new Preset();
    }
    #[Post_Condition]
    protected function __mark_test_incomplete_if_snapshot_have_changed(): void
    {
        if (count($this->__snapshot_changes) === 0) {
            return;
        }
        $this->mark_test_incomplete(implode('. ', $this->__snapshot_changes));
    }
    /**
     * The printable test case name.
     */
    public static function get_printable_test_case_name(): string
    {
        return preg_replace('/P\\\\/', '', self::class, 1);
    }
    /**
     * The printable test case method name.
     */
    public function get_printable_test_case_method_name(): string
    {
        return $this->__description;
    }
    /**
     * The latest printable test case method name.
     */
    public static function get_latest_printable_test_case_method_name(): string
    {
        return self::$__latest_description ?? '';
    }
    /**
     * The printable test case method context.
     */
    public static function get_printable_context(): array
    {
        return ['assignees' => self::$__latest_assignees, 'issues' => self::$__latest_issues, 'prs' => self::$__latest_prs, 'notes' => self::$__latest_notes];
    }
    /**
     * Opens a shell for the test case.
     */
    public function shell(): void
    {
        Shell::open();
    }
}