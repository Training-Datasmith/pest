<?php

declare(strict_types=1);

use Pest\Panic;

it('can reference a specific class', function () {
    expect(Panic::class)->toBeString();
})->see(Panic::class);

it('can reference a specific class method', function () {
    expect(Panic::with(...))->toBeCallable();
})->see([Panic::class, 'with']);
