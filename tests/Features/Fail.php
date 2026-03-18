<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;

it('may fail', function () {
    $this->fail();
})->throws(AssertionFailedError::class);

it('may fail with the given message', function () {
    $this->fail('this is a failure');
})->throws(AssertionFailedError::class, 'this is a failure');
