<?php

declare (strict_types=1);
namespace Pest\Factories;

use ParseError;
use Pest\Concerns;
use Pest\Contracts\Has_Printable_Test_Case_Name;
use Pest\Evaluators\Attributes;
use Pest\Exceptions\Dataset_Missing;
use Pest\Exceptions\Should_Not_Happen;
use Pest\Exceptions\Test_Already_Exist;
use Pest\Exceptions\Test_Closure_Must_Not_Be_Static;
use Pest\Exceptions\Test_Description_Missing;
use Pest\Factories\Concerns\Higher_Orderable;
use Pest\Support\Reflection;
use Pest\Support\Str;
use Pest\Test_Suite;
use Php_Unit\Framework\Attributes\Test_Dox;
use Php_Unit\Framework\Test_Case;
use RuntimeException;
/**
 * @internal
 */
final class Test_Case_Factory
{
    use Higher_Orderable;
    /**
     * The list of attributes.
     *
     * @var array<int, Attribute>
     */
    public array $attributes = [];
    /**
     * The FQN of the Test Case class.
     *
     * @var class-string
     */
    public string $class = Test_Case::class;
    /**
     * The list of class methods.
     *
     * @var array<string, TestCaseMethodFactory>
     */
    public array $methods = [];
    /**
     * The list of class traits.
     *
     * @var array <int, class-string>
     */
    public array $traits = [Concerns\Testable::class, Concerns\Expectable::class];
    /**
     * Creates a new Factory instance.
     */
    public function __construct(public string $filename)
    {
        $this->boot_higher_orderable();
    }
    public function make(): void
    {
        $methods = $this->methods;
        if ($methods !== []) {
            $this->evaluate($this->filename, $methods);
        }
    }
    /**
     * Creates a Test Case class using a runtime evaluate.
     *
     * @param  array<string, TestCaseMethodFactory>  $methods
     */
    public function evaluate(string $filename, array $methods): void
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            // In case Windows, strtolower drive name, like in UsesCall.
            $filename = (string) preg_replace_callback('~^(?P<drive>[a-z]+:\\\\)~i', static fn(array $match): string => strtolower($match['drive']), $filename);
        }
        $filename = str_replace('\\\\', '\\', addslashes((string) realpath($filename)));
        $root_path = Test_Suite::get_instance()->root_path;
        $relative_path = str_replace($root_path . DIRECTORY_SEPARATOR, '', $filename);
        $relative_path = ltrim($relative_path, DIRECTORY_SEPARATOR);
        $basename = basename($relative_path, '.php');
        $dot_pos = strpos($basename, '.');
        if ($dot_pos !== false) {
            $basename = substr($basename, 0, $dot_pos);
        }
        $relative_path = dirname(ucfirst($relative_path)) . DIRECTORY_SEPARATOR . $basename;
        $relative_path = str_replace(DIRECTORY_SEPARATOR, '\\', $relative_path);
        // Strip out any %-encoded octets.
        $relative_path = (string) preg_replace('|%[a-fA-F0-9][a-fA-F0-9]|', '', $relative_path);
        // Remove escaped quote sequences (maintain namespace)
        $relative_path = str_replace(array_map(fn(string $quote): string => sprintf('\%s', $quote), ['\'', '"']), '', $relative_path);
        // Limit to A-Z, a-z, 0-9, '_', '-'.
        $relative_path = (string) preg_replace('/[^A-Za-z0-9\\\\]/', '', $relative_path);
        $class_fqn = 'P\\' . $relative_path;
        if (class_exists($class_fqn)) {
            return;
        }
        $has_printable_test_case_class_fqn = sprintf('\%s', Has_Printable_Test_Case_Name::class);
        $traits_code = sprintf('use %s;', implode(', ', array_map(static fn(string $trait): string => sprintf('\%s', $trait), $this->traits)));
        $parts_fqn = explode('\\', $class_fqn);
        $class_name = array_pop($parts_fqn);
        $namespace = implode('\\', $parts_fqn);
        $base_class = sprintf('\%s', $this->class);
        if (trim($class_name) === '') {
            $class_name = 'InvalidTestName' . Str::random();
        }
        $this->attributes = [new Attribute(Test_Dox::class, [$this->filename]), ...$this->attributes];
        $attributes_code = Attributes::code($this->attributes);
        $methods_code = implode('', array_map(fn(Test_Case_Method_Factory $method_factory): string => $method_factory->build_for_evaluation(), $methods));
        try {
            $class_code = <<<PHP
            namespace {$namespace};
            
            use Pest\\Repositories\\DatasetsRepository as __PestDatasets;
            use Pest\\TestSuite as __PestTestSuite;
            
            {$attributes_code}
            #[\\AllowDynamicProperties]
            final class {$class_name} extends {$base_class} implements {$has_printable_test_case_class_fqn} {
                {$traits_code}
            
                private static \$__filename = '{$filename}';
            
                {$methods_code}
            }
            PHP;
            eval($class_code);
        } catch (ParseError $caught) {
            throw new RuntimeException(sprintf("Unable to create test case for test file at %s. \n %s", $filename, $class_code), 1, $caught);
        }
    }
    /**
     * Adds the given Method to the Test Case.
     */
    public function add_method(Test_Case_Method_Factory $method): void
    {
        if ($method->description === null) {
            throw new Test_Description_Missing($method->filename);
        }
        if (array_key_exists($method->description, $this->methods)) {
            throw new Test_Already_Exist($method->filename, $method->description);
        }
        if ($method->closure instanceof \Closure && (new \ReflectionFunction($method->closure))->is_static()) {
            throw new Test_Closure_Must_Not_Be_Static($method);
        }
        if (!$method->receives_arguments()) {
            if (!$method->closure instanceof \Closure) {
                throw Should_Not_Happen::from_message('The test closure may not be empty.');
            }
            $arguments = Reflection::get_function_arguments($method->closure);
            if ($arguments !== []) {
                throw new Dataset_Missing($method->filename, $method->description, $arguments);
            }
        }
        $this->methods[$method->description] = $method;
    }
    /**
     * Checks if a test case has a method.
     */
    public function has_method(string $method_name): bool
    {
        foreach ($this->methods as $method) {
            if ($method->description === null) {
                throw Should_Not_Happen::from_message('The test description may not be empty.');
            }
            if ($method_name === Str::evaluable($method->description)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Gets a Method by the given name.
     */
    public function get_method(string $method_name): Test_Case_Method_Factory
    {
        foreach ($this->methods as $method) {
            if ($method->description === null) {
                throw Should_Not_Happen::from_message('The test description may not be empty.');
            }
            if ($method_name === Str::evaluable($method->description)) {
                return $method;
            }
        }
        throw Should_Not_Happen::from_message(sprintf('Method %s not found.', $method_name));
    }
}