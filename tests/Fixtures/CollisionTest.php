<?php

declare(strict_types=1);

test('error', function () {
    throw new Exception('error');
})->skip(! isset($_SERVER['COLLISION_TEST']));

test('success', function () {
    expect(true)->toBeTrue();
})->skip(! isset($_SERVER['COLLISION_TEST']));
