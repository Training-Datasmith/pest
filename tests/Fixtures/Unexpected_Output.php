<?php

declare(strict_types=1);

test('output', function () {
    echo 'this is unexpected output';

    expect(true)->toBeTrue();
})->skip(! isset($_SERVER['COLLISION_TEST']));
