<?php

declare (strict_types=1);
namespace Pest\Expectations;

use Attribute;
use Pest\Arch\Contracts\Arch_Expectation;
use Pest\Arch\Expectations\Targeted;
use Pest\Arch\Expectations\To_Be_Used_In;
use Pest\Arch\Expectations\To_Be_Used_In_Nothing;
use Pest\Arch\Expectations\To_Use;
use Pest\Arch\Group_Arch_Expectation;
use Pest\Arch\Pending_Arch_Expectation;
use Pest\Arch\Single_Arch_Expectation;
use Pest\Arch\Support\File_Line_Finder;
use Pest\Exceptions\Invalid_Expectation;
use Pest\Exceptions\Missing_Dependency;
use Pest\Expectation;
use Pest\Support\Arr;
use Pest\Support\Exporter;
use Pest\Support\Reflection;
use Php_Unit\Architecture\Elements\Object_Description;
use Php_Unit\Framework\Assertion_Failed_Error;
use Php_Unit\Framework\Expectation_Failed_Exception;
use ReflectionMethod;
use ReflectionProperty;
use Spoofchecker;
use stdClass;
/**
 * @internal
 *
 * @template TValue
 *
 * @mixin Expectation<TValue>
 */
final readonly class Opposite_Expectation
{
    /**
     * Creates a new opposite expectation.
     *
     * @param  Expectation<TValue>  $original
     */
    public function __construct(private Expectation $original)
    {
    }
    /**
     * Asserts that the value array not has the provided $keys.
     *
     * @param  array<int, int|string|array<int-string, mixed>>  $keys
     * @return Expectation<TValue>
     */
    public function to_have_keys(array $keys): Expectation
    {
        foreach ($keys as $k => $key) {
            try {
                if (is_array($key)) {
                    $this->to_have_keys(array_keys(Arr::dot($key, $k . '.')));
                } else {
                    $this->original->to_have_key($key);
                }
            } catch (Expectation_Failed_Exception) {
                continue;
            }
            $this->throw_expectation_failed_exception('toHaveKey', [$key]);
        }
        return $this->original;
    }
    /**
     * Asserts that the given expectation target does not use any of the given dependencies.
     *
     * @param  array<int, string>|string  $targets
     */
    public function to_use(array|string $targets): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Group_Arch_Expectation::from_expectations($original, array_map(fn(string $target): Single_Arch_Expectation => To_Use::make($original, $target)->opposite(fn() => $this->throw_expectation_failed_exception('toUse', $target)), is_string($targets) ? [$targets] : $targets));
    }
    /**
     * Asserts that the given expectation target does not have the given permissions
     */
    public function to_have_file_system_permissions(string $permissions): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => substr(sprintf('%o', fileperms($object->path)), -4) !== $permissions, sprintf('permissions not to be [%s]', $permissions), File_Line_Finder::where(fn(string $line): bool => str_contains($line, '<?php')));
    }
    /**
     * Not supported.
     */
    public function to_have_line_count_less_than(): Arch_Expectation
    {
        throw Invalid_Expectation::from_methods(['not', 'toHaveLineCountLessThan']);
    }
    /**
     * Not supported.
     */
    public function to_have_methods_documented(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || array_filter(Reflection::get_methods_from_reflection_class($object->reflection_class), fn(ReflectionMethod $method): bool => (enum_exists($object->name) === false || in_array($method->name, ['from', 'tryFrom', 'cases'], true) === false) && realpath($method->get_file_name() ?: '/') === realpath($object->path) && $method->get_doc_comment() !== false) === [], 'to have methods without documentation / annotations', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Not supported.
     */
    public function to_have_properties_documented(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || array_filter(Reflection::get_properties_from_reflection_class($object->reflection_class), fn(ReflectionProperty $property): bool => (enum_exists($object->name) === false || in_array($property->name, ['value', 'name'], true) === false) && realpath($property->get_declaring_class()->get_file_name() ?: '/') === realpath($object->path) && $property->is_promoted() === false && $property->get_doc_comment() !== false) === [], 'to have properties without documentation / annotations', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target does not use the "declare(strict_types=1)" declaration.
     */
    public function to_use_strict_types(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => !(bool) preg_match('/^<\?php\s+declare\(.*?strict_types\s?=\s?1.*?\);/', (string) file_get_contents($object->path)), 'not to use strict types', File_Line_Finder::where(fn(string $line): bool => str_contains($line, '<?php')));
    }
    /**
     * Asserts that the given expectation target does not use the strict equality operator.
     */
    public function to_use_strict_equality(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => !str_contains((string) file_get_contents($object->path), ' === ') && !str_contains((string) file_get_contents($object->path), ' !== '), 'to use strict equality', File_Line_Finder::where(fn(string $line): bool => str_contains($line, ' === ') || str_contains($line, ' !== ')));
    }
    /**
     * Asserts that the given expectation target is not final.
     */
    public function to_be_final(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => !enum_exists($object->name) && (isset($object->reflection_class) === false || !$object->reflection_class->is_final()), 'not to be final', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target is not readonly.
     */
    public function to_be_readonly(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make(
            $original,
            fn(Object_Description $object): bool => !enum_exists($object->name) && (isset($object->reflection_class) === false || !$object->reflection_class->is_read_only()) && assert(true),
            // @phpstan-ignore-line
            'not to be readonly',
            File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class'))
        );
    }
    /**
     * Asserts that the given expectation target is not trait.
     */
    public function to_be_trait(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || !$object->reflection_class->is_trait(), 'not to be trait', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation targets are not traits.
     */
    public function to_be_traits(): Arch_Expectation
    {
        return $this->to_be_trait();
    }
    /**
     * Asserts that the given expectation target is not abstract.
     */
    public function to_be_abstract(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || !$object->reflection_class->is_abstract(), 'not to be abstract', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target does not have a specific method.
     *
     * @param  array<int, string>|string  $method
     */
    public function to_have_method(array|string $method): Arch_Expectation
    {
        $methods = is_array($method) ? $method : [$method];
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => array_filter($methods, fn(string $method): bool => isset($object->reflection_class) === false || $object->reflection_class->has_method($method)) === [], 'to not have methods: ' . implode(', ', $methods), File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target does not have suspicious characters.
     */
    public function to_have_suspicious_characters(): Arch_Expectation
    {
        if (!class_exists(Spoofchecker::class)) {
            throw new Missing_Dependency(__FUNCTION__, 'ext-intl >= 2.0');
        }
        $checker = new Spoofchecker();
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => !$checker->is_suspicious((string) file_get_contents($object->path)), 'to not include suspicious characters', File_Line_Finder::where(fn(string $line): bool => $checker->is_suspicious($line)));
    }
    /**
     * Asserts that the given expectation target does not have the given methods.
     *
     * @param  array<int, string>  $methods
     */
    public function to_have_methods(array $methods): Arch_Expectation
    {
        return $this->to_have_method($methods);
    }
    /**
     * Asserts that the given expectation target not to have the public methods besides the given methods.
     *
     * @param  array<int, string>|string  $methods
     */
    public function to_have_public_methods_besides(array|string $methods): Arch_Expectation
    {
        $methods = is_array($methods) ? $methods : [$methods];
        $state = new stdClass();
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, function (Object_Description $object) use ($methods, &$state): bool {
            $reflection_methods = isset($object->reflection_class) ? Reflection::get_methods_from_reflection_class($object->reflection_class, ReflectionMethod::IS_PUBLIC) : [];
            foreach ($reflection_methods as $reflection_method) {
                if (!in_array($reflection_method->name, $methods, true)) {
                    $state->contains = 'public function ' . $reflection_method->name;
                    return false;
                }
            }
            return true;
        }, $methods === [] ? 'not to have public methods' : sprintf("not to have public methods besides '%s'", implode("', '", $methods)), File_Line_Finder::where(fn(string $line): bool => str_contains($line, (string) $state->contains)));
    }
    /**
     * Asserts that the given expectation target not to have the public methods.
     */
    public function to_have_public_methods(): Arch_Expectation
    {
        return $this->to_have_public_methods_besides([]);
    }
    /**
     * Asserts that the given expectation target not to have the protected methods besides the given methods.
     *
     * @param  array<int, string>|string  $methods
     */
    public function to_have_protected_methods_besides(array|string $methods): Arch_Expectation
    {
        $methods = is_array($methods) ? $methods : [$methods];
        $state = new stdClass();
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, function (Object_Description $object) use ($methods, &$state): bool {
            $reflection_methods = isset($object->reflection_class) ? Reflection::get_methods_from_reflection_class($object->reflection_class, ReflectionMethod::IS_PROTECTED) : [];
            foreach ($reflection_methods as $reflection_method) {
                if (!in_array($reflection_method->name, $methods, true)) {
                    $state->contains = 'protected function ' . $reflection_method->name;
                    return false;
                }
            }
            return true;
        }, $methods === [] ? 'not to have protected methods' : sprintf("not to have protected methods besides '%s'", implode("', '", $methods)), File_Line_Finder::where(fn(string $line): bool => str_contains($line, (string) $state->contains)));
    }
    /**
     * Asserts that the given expectation target not to have the protected methods.
     */
    public function to_have_protected_methods(): Arch_Expectation
    {
        return $this->to_have_protected_methods_besides([]);
    }
    /**
     * Asserts that the given expectation target not to have the private methods besides the given methods.
     *
     * @param  array<int, string>|string  $methods
     */
    public function to_have_private_methods_besides(array|string $methods): Arch_Expectation
    {
        $methods = is_array($methods) ? $methods : [$methods];
        $state = new stdClass();
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, function (Object_Description $object) use ($methods, &$state): bool {
            $reflection_methods = isset($object->reflection_class) ? Reflection::get_methods_from_reflection_class($object->reflection_class, ReflectionMethod::IS_PRIVATE) : [];
            foreach ($reflection_methods as $reflection_method) {
                if (!in_array($reflection_method->name, $methods, true)) {
                    $state->contains = 'private function ' . $reflection_method->name;
                    return false;
                }
            }
            return true;
        }, $methods === [] ? 'not to have private methods' : sprintf("not to have private methods besides '%s'", implode("', '", $methods)), File_Line_Finder::where(fn(string $line): bool => str_contains($line, (string) $state->contains)));
    }
    /**
     * Asserts that the given expectation target not to have the private methods.
     */
    public function to_have_private_methods(): Arch_Expectation
    {
        return $this->to_have_private_methods_besides([]);
    }
    /**
     * Asserts that the given expectation target is not enum.
     */
    public function to_be_enum(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || !$object->reflection_class->is_enum(), 'not to be enum', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation targets are not enums.
     */
    public function to_be_enums(): Arch_Expectation
    {
        return $this->to_be_enum();
    }
    /**
     * Asserts that the given expectation targets is not class.
     */
    public function to_be_class(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => !class_exists($object->name), 'not to be class', File_Line_Finder::where(fn(string $line): bool => true));
    }
    /**
     * Asserts that the given expectation targets are not classes.
     */
    public function to_be_classes(): Arch_Expectation
    {
        return $this->to_be_class();
    }
    /**
     * Asserts that the given expectation target is not interface.
     */
    public function to_be_interface(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || !$object->reflection_class->is_interface(), 'not to be interface', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation targets are not interfaces.
     */
    public function to_be_interfaces(): Arch_Expectation
    {
        return $this->to_be_interface();
    }
    /**
     * Asserts that the given expectation target to be not subclass of the given class.
     */
    public function to_extend(string $class): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || !$object->reflection_class->is_subclass_of($class), sprintf("not to extend '%s'", $class), File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target to be not have any parent class.
     */
    public function to_extend_nothing(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || $object->reflection_class->get_parent_class() !== false, 'to extend a class', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target not to use the given trait.
     */
    public function to_use_trait(string $trait): Arch_Expectation
    {
        return $this->to_use_traits($trait);
    }
    /**
     * Asserts that the given expectation target not to use the given traits.
     *
     * @param  array<int, string>|string  $traits
     */
    public function to_use_traits(array|string $traits): Arch_Expectation
    {
        $traits = is_array($traits) ? $traits : [$traits];
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, function (Object_Description $object) use ($traits): bool {
            foreach ($traits as $trait) {
                if (isset($object->reflection_class) && in_array($trait, $object->reflection_class->get_trait_names(), true)) {
                    return false;
                }
            }
            return true;
        }, "not to use traits '" . implode("', '", $traits) . "'", File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target not to implement the given interfaces.
     *
     * @param  array<int, string>|string  $interfaces
     */
    public function to_implement(array|string $interfaces): Arch_Expectation
    {
        $interfaces = is_array($interfaces) ? $interfaces : [$interfaces];
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, function (Object_Description $object) use ($interfaces): bool {
            foreach ($interfaces as $interface) {
                if (isset($object->reflection_class) && $object->reflection_class->implements_interface($interface)) {
                    return false;
                }
            }
            return true;
        }, "not to implement '" . implode("', '", $interfaces) . "'", File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target to not implement any interfaces.
     */
    public function to_implement_nothing(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || $object->reflection_class->get_interface_names() !== [], 'to implement an interface', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Not supported.
     */
    public function to_only_implement(): never
    {
        throw Invalid_Expectation::from_methods(['not', 'toOnlyImplement']);
    }
    /**
     * Asserts that the given expectation target to not have the given prefix.
     */
    public function to_have_prefix(string $prefix): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || !str_starts_with((string) $object->reflection_class->get_short_name(), $prefix), "not to have prefix '{$prefix}'", File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target to not have the given suffix.
     */
    public function to_have_suffix(string $suffix): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || !str_ends_with((string) $object->reflection_class->get_name(), $suffix), "not to have suffix '{$suffix}'", File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Not supported.
     */
    public function to_only_use(): never
    {
        throw Invalid_Expectation::from_methods(['not', 'toOnlyUse']);
    }
    /**
     * Not supported.
     */
    public function to_use_nothing(): never
    {
        throw Invalid_Expectation::from_methods(['not', 'toUseNothing']);
    }
    /**
     * Asserts that the given expectation dependency is not used.
     */
    public function to_be_used(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return To_Be_Used_In_Nothing::make($original);
    }
    /**
     * Asserts that the given expectation dependency is not used by any of the given targets.
     *
     * @param  array<int, string>|string  $targets
     */
    public function to_be_used_in(array|string $targets): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Group_Arch_Expectation::from_expectations($original, array_map(fn(string $target): Group_Arch_Expectation => To_Be_Used_In::make($original, $target)->opposite(fn() => $this->throw_expectation_failed_exception('toBeUsedIn', $target)), is_string($targets) ? [$targets] : $targets));
    }
    public function to_only_be_used_in(): never
    {
        throw Invalid_Expectation::from_methods(['not', 'toOnlyBeUsedIn']);
    }
    /**
     * Asserts that the given expectation dependency is not used.
     */
    public function to_be_used_in_nothing(): never
    {
        throw Invalid_Expectation::from_methods(['not', 'toBeUsedInNothing']);
    }
    /**
     * Asserts that the given expectation dependency is not an invokable class.
     */
    public function to_be_invokable(): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || !$object->reflection_class->has_method('__invoke'), 'to not be invokable', File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Asserts that the given expectation target not to have the given attribute.
     */
    public function to_have_attribute(string $attribute): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make($original, fn(Object_Description $object): bool => isset($object->reflection_class) === false || $object->reflection_class->get_attributes($attribute) === [], "to not have attribute '{$attribute}'", File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class')));
    }
    /**
     * Handle dynamic method calls into the original expectation.
     *
     * @param  array<int, mixed>  $arguments
     * @return Expectation<TValue>|Expectation<mixed>|never
     */
    public function __call(string $name, array $arguments): Expectation
    {
        try {
            if (!is_object($this->original->value) && method_exists(Pending_Arch_Expectation::class, $name)) {
                throw Invalid_Expectation::from_methods(['not', $name]);
            }
            /* @phpstan-ignore-next-line */
            $this->original->{$name}(...$arguments);
        } catch (Expectation_Failed_Exception|Assertion_Failed_Error) {
            return $this->original;
        }
        $this->throw_expectation_failed_exception($name, $arguments);
    }
    /**
     * Handle dynamic properties gets into the original expectation.
     *
     * @return Expectation<TValue>|Expectation<mixed>|never
     */
    public function __get(string $name): Expectation
    {
        try {
            if (!is_object($this->original->value) && method_exists(Pending_Arch_Expectation::class, $name)) {
                throw Invalid_Expectation::from_methods(['not', $name]);
            }
            $this->original->{$name};
            // @phpstan-ignore-line
        } catch (Expectation_Failed_Exception) {
            return $this->original;
        }
        $this->throw_expectation_failed_exception($name);
    }
    /**
     * Creates a new expectation failed exception with a nice readable message.
     *
     * @param  array<int, mixed>|string  $arguments
     */
    public function throw_expectation_failed_exception(string $name, array|string $arguments = []): never
    {
        $arguments = is_array($arguments) ? $arguments : [$arguments];
        $exporter = Exporter::default();
        $to_string = fn(mixed $argument): string => $exporter->shortened_export($argument);
        throw new Expectation_Failed_Exception(sprintf('Expecting %s not %s %s.', $to_string($this->original->value), strtolower((string) preg_replace('/(?<!\ )[A-Z]/', ' $0', $name)), implode(' ', array_map(fn(mixed $argument): string => $to_string($argument), $arguments))));
    }
    /**
     * Asserts that the given expectation target does not have a constructor method.
     */
    public function to_have_constructor(): Arch_Expectation
    {
        return $this->to_have_method('__construct');
    }
    /**
     * Asserts that the given expectation target does not have a destructor method.
     */
    public function to_have_destructor(): Arch_Expectation
    {
        return $this->to_have_method('__destruct');
    }
    /**
     * Asserts that the given expectation target is not a backed enum of given type.
     */
    private function to_be_backed_enum(string $backing_type): Arch_Expectation
    {
        /** @var Expectation<array<int, string>|string> $original */
        $original = $this->original;
        return Targeted::make(
            $original,
            fn(Object_Description $object): bool => isset($object->reflection_class) === false || !$object->reflection_class->is_enum() || !(new \Reflection_Enum($object->name))->is_backed() || (string) (new \Reflection_Enum($object->name))->get_backing_type() !== $backing_type,
            // @phpstan-ignore-line
            'not to be ' . $backing_type . ' backed enum',
            File_Line_Finder::where(fn(string $line): bool => str_contains($line, 'class'))
        );
    }
    /**
     * Asserts that the given expectation targets are not string backed enums.
     */
    public function to_be_string_backed_enums(): Arch_Expectation
    {
        return $this->to_be_string_backed_enum();
    }
    /**
     * Asserts that the given expectation targets are not int backed enums.
     */
    public function to_be_int_backed_enums(): Arch_Expectation
    {
        return $this->to_be_int_backed_enum();
    }
    /**
     * Asserts that the given expectation target is not a string backed enum.
     */
    public function to_be_string_backed_enum(): Arch_Expectation
    {
        return $this->to_be_backed_enum('string');
    }
    /**
     * Asserts that the given expectation target is not an int backed enum.
     */
    public function to_be_int_backed_enum(): Arch_Expectation
    {
        return $this->to_be_backed_enum('int');
    }
}