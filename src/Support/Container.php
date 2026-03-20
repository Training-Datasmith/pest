<?php

declare (strict_types=1);
namespace Pest\Support;

use Pest\Exceptions\Should_Not_Happen;
use ReflectionClass;
use ReflectionParameter;
/**
 * @internal
 */
final class Container
{
    /**
     * The instance of the container.
     */
    private static ?Container $instance = null;
    /**
     * @var array<string, object|string>
     */
    private array $instances = [];
    /**
     * Gets a new or already existing container.
     */
    public static function get_instance(): self
    {
        if (!self::$instance instanceof Container) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * Gets a dependency from the container.
     */
    public function get(string $id): object|string
    {
        if (!array_key_exists($id, $this->instances)) {
            /** @var class-string $id */
            $this->instances[$id] = $this->build($id);
        }
        return $this->instances[$id];
    }
    /**
     * Adds the given instance to the container.
     *
     * @return $this
     */
    public function add(string $id, object|string $instance): self
    {
        $this->instances[$id] = $instance;
        return $this;
    }
    /**
     * Tries to build the given instance.
     *
     * @template TObject of object
     *
     * @param  class-string<TObject>  $id
     * @return TObject
     */
    private function build(string $id): object
    {
        $reflection_class = new ReflectionClass($id);
        if ($reflection_class->is_instantiable()) {
            $constructor = $reflection_class->get_constructor();
            if ($constructor instanceof \ReflectionMethod) {
                $params = array_map(function (ReflectionParameter $param) use ($id): object|string {
                    $candidate = Reflection::get_parameter_class_name($param);
                    if ($candidate === null) {
                        $type = $param->get_type();
                        /* @phpstan-ignore-next-line */
                        if ($type instanceof \Reflection_Type && $type->is_builtin()) {
                            $candidate = $param->get_name();
                        } else {
                            throw Should_Not_Happen::from_message(sprintf('The type of `$%s` in `%s` cannot be determined.', $id, $param->get_name()));
                        }
                    }
                    return $this->get($candidate);
                }, $constructor->get_parameters());
                return $reflection_class->new_instance_args($params);
            }
            return $reflection_class->new_instance();
        }
        throw Should_Not_Happen::from_message(sprintf('A dependency with the name `%s` cannot be resolved.', $id));
    }
}