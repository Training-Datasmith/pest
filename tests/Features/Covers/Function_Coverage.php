<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversFunction;

function testCoversFunction()
{
}

it('uses the correct PHPUnit attribute for function', function () {
    $attributes = (new ReflectionClass($this))->getAttributes();

    expect($attributes[1]->getName())->toBe(CoversFunction::class);
    expect($attributes[1]->getArguments()[0])->toBe('testCoversFunction');
})->coversFunction('testCoversFunction');
