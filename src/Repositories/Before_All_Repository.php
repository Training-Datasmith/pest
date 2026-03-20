<?php

declare (strict_types=1);
namespace Pest\Repositories;

use Closure;
use Pest\Exceptions\Before_All_Already_Exist;
use Pest\Support\Null_Closure;
use Pest\Support\Reflection;
/**
 * @internal
 */
final class Before_All_Repository
{
    /**
     * @var array<string, Closure>
     */
    private array $state = [];
    /**
     * Runs one before all closure, and unsets it from the repository.
     */
    public function pop(string $filename): Closure
    {
        $closure = $this->get($filename);
        unset($this->state[$filename]);
        return $closure;
    }
    /**
     * Sets a before all closure.
     */
    public function set(Closure $closure): void
    {
        $filename = Reflection::get_file_name_from_closure($closure);
        if (array_key_exists($filename, $this->state)) {
            throw new Before_All_Already_Exist($filename);
        }
        $this->state[$filename] = $closure;
    }
    /**
     * Gets a before all closure by the given filename.
     */
    public function get(string $filename): Closure
    {
        return $this->state[$filename] ?? Null_Closure::create();
    }
}