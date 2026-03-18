<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversTrait as PHPUnitCoversTrait;
use Tests\Fixtures\Covers\CoversTrait;

it('uses the correct PHPUnit attribute for trait', function () {
    $attributes = (new ReflectionClass($this))->getAttributes();

    expect($attributes[1]->getName())->toBe(PHPUnitCoversTrait::class);
    expect($attributes[1]->getArguments()[0])->toBe('Tests\Fixtures\Covers\CoversTrait');
})->coversTrait(CoversTrait::class);
