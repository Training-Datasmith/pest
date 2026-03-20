<?php

declare (strict_types=1);
namespace Pest\Pending_Calls;

use Closure;
use Nuno_Maduro\Collision\Adapters\Phpunit\Printers\Default_Printer;
use Pest\Test_Suite;
/**
 * @internal
 */
final class Uses_Call
{
    /**
     * Contains a global before each hook closure to be executed.
     *
     * Array indices here matter. They are mapped as follows:
     *
     * - `0` => `beforeAll`
     * - `1` => `beforeEach`
     * - `2` => `afterEach`
     * - `3` => `afterAll`
     *
     * @var array<int, Closure>
     */
    private array $hooks = [];
    /**
     * Holds the targets of the uses.
     *
     * @var array<int, string>
     */
    private array $targets;
    /**
     * Holds the groups of the uses.
     *
     * @var array<int, string>
     */
    private array $groups = [];
    /**
     * Creates a new Pending Call.
     *
     * @param  array<int, string>  $classAndTraits
     */
    public function __construct(private readonly string $filename, private array $class_and_traits)
    {
        $this->targets = [$filename];
    }
    /**
     * @deprecated Use `pest()->printer()->compact()` instead.
     */
    public function compact(): self
    {
        Default_Printer::compact(true);
        return $this;
    }
    /**
     * Specifies the class or traits to use.
     *
     * @alias extend
     */
    public function use(string ...$class_and_traits): self
    {
        return $this->extend(...$class_and_traits);
    }
    /**
     * Specifies the class or traits to use.
     */
    public function extend(string ...$class_and_traits): self
    {
        $this->class_and_traits = array_merge($this->class_and_traits, array_values($class_and_traits));
        return $this;
    }
    /**
     * The directories or file where the class or traits should be used.
     */
    public function in(string ...$targets): self
    {
        $targets = array_map(function (string $path): string {
            $start_char = DIRECTORY_SEPARATOR;
            if ('\\' === DIRECTORY_SEPARATOR || preg_match('~\A[A-Z]:(?![^/\\\\])~i', $path) > 0) {
                $path = (string) preg_replace_callback('~^(?P<drive>[a-z]+:\\\\)~i', fn(array $match): string => strtolower($match['drive']), $path);
                $start_char = strtolower((string) preg_replace('~^([a-z]+:\\\\).*$~i', '$1', __DIR__));
            }
            return str_starts_with($path, $start_char) ? $path : implode(DIRECTORY_SEPARATOR, [is_dir($this->filename) ? $this->filename : dirname($this->filename), $path]);
        }, $targets);
        $this->targets = array_reduce($targets, function (array $accumulator, string $target): array {
            if (($matches = glob($target)) !== false) {
                foreach ($matches as $file) {
                    $accumulator[] = (string) realpath($file);
                }
            }
            return $accumulator;
        }, []);
        return $this;
    }
    /**
     * Sets the test group(s).
     */
    public function group(string ...$groups): self
    {
        $this->groups = array_values($groups);
        return $this;
    }
    /**
     * Sets the global beforeAll test hook.
     */
    public function before_all(Closure $hook): self
    {
        $this->hooks[0] = $hook;
        return $this;
    }
    /**
     * Sets the global beforeEach test hook.
     */
    public function before_each(Closure $hook): self
    {
        $this->hooks[1] = $hook;
        return $this;
    }
    /**
     * Sets the global afterEach test hook.
     */
    public function after_each(Closure $hook): self
    {
        $this->hooks[2] = $hook;
        return $this;
    }
    /**
     * Sets the global afterAll test hook.
     */
    public function after_all(Closure $hook): self
    {
        $this->hooks[3] = $hook;
        return $this;
    }
    /**
     * Creates the Call.
     */
    public function __destruct()
    {
        Test_Suite::get_instance()->tests->use($this->class_and_traits, $this->groups, $this->targets, $this->hooks);
    }
}