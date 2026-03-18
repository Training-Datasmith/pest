<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Fixtures\Covers\CoversClass1;

covers([CoversClass1::class]);

it('uses the correct PHPUnit attribute for class', function () {
    $attributes = (new ReflectionClass($this))->getAttributes();

    expect($attributes[1]->getName())->toBe(CoversClass::class);
    expect($attributes[1]->getArguments()[0])->toBe('Tests\Fixtures\Covers\CoversClass1');
});
