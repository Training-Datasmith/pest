<?php

declare (strict_types=1);
namespace Pest\Repositories;

use Closure;
use Mockery;
use Pest\Pending_Calls\After_Each_Call;
use Pest\Support\Chainable_Closure;
use Pest\Support\Null_Closure;
/**
 * @internal
 */
final class After_Each_Repository
{
    /**
     * @var array<string, Closure>
     */
    private array $state = [];
    /**
     * Sets a after each closure.
     */
    public function set(string $filename, After_Each_Call $after_each_call, Closure $after_each_test_case): void
    {
        if (array_key_exists($filename, $this->state)) {
            $from_after_each_test_case = $this->state[$filename];
            $after_each_test_case = Chainable_Closure::bound($from_after_each_test_case, $after_each_test_case)->bind_to($after_each_call, $after_each_call::class);
        }
        assert($after_each_test_case instanceof Closure);
        $this->state[$filename] = $after_each_test_case;
    }
    /**
     * Gets an after each closure by the given filename.
     */
    public function get(string $filename): Closure
    {
        $after_each = $this->state[$filename] ?? Null_Closure::create();
        return Chainable_Closure::bound(function (): void {
            if (class_exists(Mockery::class)) {
                if ($container = Mockery::get_container()) {
                    /* @phpstan-ignore-next-line */
                    $this->add_to_assertion_count($container->mockery_get_expectation_count());
                }
                Mockery::close();
            }
        }, $after_each);
    }
}