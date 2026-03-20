<?php

declare (strict_types=1);
namespace Pest\Factories;

use Closure;
use Pest\Evaluators\Attributes;
use Pest\Exceptions\Should_Not_Happen;
use Pest\Factories\Concerns\Higher_Orderable;
use Pest\Repositories\Datasets_Repository;
use Pest\Support\Description;
use Pest\Support\Str;
use Pest\Test_Suite;
use Php_Unit\Framework\Assert;
use Php_Unit\Framework\Attributes\Data_Provider;
use Php_Unit\Framework\Attributes\Depends;
use Php_Unit\Framework\Attributes\Test;
use Php_Unit\Framework\Attributes\Test_Dox;
use Php_Unit\Framework\Test_Case;
/**
 * @internal
 */
final class Test_Case_Method_Factory
{
    use Higher_Orderable;
    /**
     * The list of attributes.
     *
     * @var array<int, Attribute>
     */
    public array $attributes = [];
    /**
     * The test's describing, if any.
     *
     * @var array<int, Description>
     */
    public array $describing = [];
    /**
     * The test's description, if any.
     */
    public ?string $description = null;
    /**
     * The test's number of repetitions.
     */
    public int $repetitions = 1;
    /**
     * Determines if the test is a "todo".
     */
    public bool $todo = false;
    /**
     * The associated issue numbers.
     *
     * @var array<int, int>
     */
    public array $issues = [];
    /**
     * The test assignees.
     *
     * @var array<int, string>
     */
    public array $assignees = [];
    /**
     * The associated PRs numbers.
     *
     * @var array<int, int>
     */
    public array $prs = [];
    /**
     * The test's notes.
     *
     * @var array<int, string>
     */
    public array $notes = [];
    /**
     * The test's datasets.
     *
     * @var array<Closure|iterable<int|string, mixed>|string>
     */
    public array $datasets = [];
    /**
     * The test's dependencies.
     *
     * @var array<int, string>
     */
    public array $depends = [];
    /**
     * The test's groups.
     *
     * @var array<int, string>
     */
    public array $groups = [];
    /**
     * @see This property is not actually used in the codebase, it's only here to make Rector happy.
     */
    public bool $__ran = false;
    /**
     * Creates a new test case method factory instance.
     */
    public function __construct(public string $filename, public ?Closure $closure)
    {
        $this->closure ??= function (): void {
            Assert::get_count() > 0 || $this->does_not_perform_assertions() ?: self::mark_test_incomplete();
            // @phpstan-ignore-line
        };
        $this->boot_higher_orderable();
    }
    /**
     * Sets the test's hooks, and runs any proxy to the test case.
     */
    public function set_up(Test_Case $concrete): void
    {
        $concrete::flush();
        // @phpstan-ignore-line
        if ($this->description === null) {
            throw Should_Not_Happen::from_message('Description can not be empty.');
        }
        $test_case = Test_Suite::get_instance()->tests->get($this->filename);
        assert($test_case instanceof Test_Case_Factory);
        $test_case->factory_proxies->proxy($concrete);
        $this->factory_proxies->proxy($concrete);
    }
    /**
     * Flushes the test case.
     */
    public function tear_down(Test_Case $concrete): void
    {
        $concrete::flush();
        // @phpstan-ignore-line
    }
    /**
     * Creates the test's closure.
     */
    public function get_closure(): Closure
    {
        $closure = $this->closure;
        $test_case = Test_Suite::get_instance()->tests->get($this->filename);
        assert($test_case instanceof Test_Case_Factory);
        $method = $this;
        return function (...$arguments) use ($test_case, $method, $closure): mixed {
            /* @var TestCase $this */
            $test_case->proxies->proxy($this);
            $method->proxies->proxy($this);
            $test_case->chains->chain($this);
            $method->chains->chain($this);
            $this->__ran = true;
            return \Pest\Support\Closure::bind($closure, $this, self::class)(...$arguments);
        };
    }
    /**
     * Determine if the test case will receive argument input from Pest, or not.
     */
    public function receives_arguments(): bool
    {
        return $this->datasets !== [] || $this->depends !== [] || $this->repetitions > 1;
    }
    /**
     * Creates a PHPUnit method as a string ready for evaluation.
     */
    public function build_for_evaluation(): string
    {
        if ($this->description === null) {
            throw Should_Not_Happen::from_message('The test description may not be empty.');
        }
        $method_name = Str::evaluable($this->description);
        $datasets_code = '';
        $this->attributes = [new Attribute(Test::class, []), new Attribute(Test_Dox::class, [str_replace('*/', '{@*}', $this->description)]), ...$this->attributes];
        foreach ($this->depends as $depend) {
            $depend = Str::evaluable($this->describing === [] ? $depend : Str::describe($this->describing, $depend));
            $this->attributes[] = new Attribute(Depends::class, [$depend]);
        }
        if ($this->datasets !== [] || $this->repetitions > 1) {
            $data_provider_name = $method_name . '_dataset';
            $this->attributes[] = new Attribute(Data_Provider::class, [$data_provider_name]);
            $datasets_code = $this->build_dataset_for_evaluation($method_name, $data_provider_name);
        }
        $attributes_code = Attributes::code($this->attributes);
        return <<<PHP
        {$attributes_code}
            public function {$method_name}(...\$arguments)
            {
                return \$this->__runTest(
                    \$this->__test,
                    ...\$arguments,
                );
            }
        {$datasets_code}
        PHP;
    }
    /**
     * Creates a PHPUnit Data Provider as a string ready for evaluation.
     */
    private function build_dataset_for_evaluation(string $method_name, string $data_provider_name): string
    {
        $datasets = $this->datasets;
        if ($this->repetitions > 1) {
            $datasets = [range(1, $this->repetitions), ...$datasets];
        }
        Datasets_Repository::with($this->filename, $method_name, $datasets);
        return <<<EOF
        
                public static function {$data_provider_name}()
                {
                    return __PestDatasets::get(self::\$__filename, "{$method_name}");
                }
        
        EOF;
    }
}