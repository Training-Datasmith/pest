<?php

declare(strict_types=1);

// This file demonstrates Pest test syntax.
// In a real project, test files live in the tests/ directory and are run via: ./vendor/bin/pest

// --- Example 1: Basic test ---
test('strings can be trimmed', function () {
    $result = trim('  hello world  ');
    expect($result)->toBe('hello world');
});

// --- Example 2: Using it() with descriptive text ---
it('adds two numbers correctly', function () {
    expect(1 + 1)->toBe(2);
});

// --- Example 3: Grouped tests with describe() ---
describe('array helpers', function () {
    it('can filter arrays', function () {
        $result = array_filter([1, 2, 3, 4, 5], fn($n) => $n % 2 === 0);
        expect($result)->toBe([1 => 2, 3 => 4]);
    });

    it('can map arrays', function () {
        $result = array_map(fn($n) => $n * 2, [1, 2, 3]);
        expect($result)->toBe([2, 4, 6]);
    });
});

// --- Example 4: Dataset-driven (data provider) tests ---
dataset('valid emails', [
    'basic email'    => ['user@example.com'],
    'subdomain'      => ['user@mail.example.com'],
    'plus address'   => ['user+tag@example.com'],
]);

test('valid email addresses pass validation', function (string $email) {
    expect(filter_var($email, FILTER_VALIDATE_EMAIL))->not()->toBeFalse();
})->with('valid emails');

// --- Example 5: Exception testing ---
test('throws on division by zero', function () {
    expect(fn() => 1 / 0)->toThrow(\DivisionByZeroError::class);
});

// --- Example 6: Higher-order tests (chaining expectations) ---
$user = new stdClass();
$user->name = 'Alice';
$user->age = 30;
$user->active = true;

expect($user)
    ->name->toBe('Alice')
    ->age->toBeGreaterThan(18)
    ->active->toBeTrue();
