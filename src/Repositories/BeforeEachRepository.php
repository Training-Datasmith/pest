<?php

declare (strict_types=1);
namespace Pest\Repositories;

use Closure;
use Pest\Pending_Calls\Before_Each_Call;
use Pest\Support\Chainable_Closure;
use Pest\Support\Null_Closure;
/**
 * @internal
 */
final class Before_Each_Repository
{
    /**
     * @var array<string, array{0: Closure, 1: Closure}>
     */
    private array $state = [];
    /**
     * Sets a before each closure.
     */
    public function set(string $filename, Before_Each_Call $before_each_call, Closure $before_each_test_call, Closure $before_each_test_case): void
    {
        if (array_key_exists($filename, $this->state)) {
            [$from_before_each_test_call, $from_before_each_test_case] = $this->state[$filename];
            $before_each_test_call = Chainable_Closure::unbound($from_before_each_test_call, $before_each_test_call);
            $before_each_test_case = Chainable_Closure::bound($from_before_each_test_case, $before_each_test_case)->bind_to($before_each_call, $before_each_call::class);
            assert($before_each_test_case instanceof Closure);
        }
        $this->state[$filename] = [$before_each_test_call, $before_each_test_case];
    }
    /**
     * Gets a before each closure by the given filename.
     *
     * @return array{0: Closure, 1: Closure}
     */
    public function get(string $filename): array
    {
        $closures = $this->state[$filename] ?? [];
        return [$closures[0] ?? Null_Closure::create(), $closures[1] ?? Null_Closure::create()];
    }
}