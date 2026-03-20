<?php

declare(strict_types=1);

use Pest\Arch\Exceptions\ArchExpectationFailedException;
use Pest\Expectation;

it('passes', function () {
    expect(Expectation::class)->toHaveLineCountLessThan(2000);
});

it('fails', function () {
    expect(Expectation::class)->toHaveLineCountLessThan(10);
})->throws(ArchExpectationFailedException::class);
