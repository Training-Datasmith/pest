<?php

declare(strict_types=1);

use Tests\CustomTestCase\CustomTestCase;

pest()->use(CustomTestCase::class)->in(__DIR__);

test('closure was bound to CustomTestCase', function () {
    $this->assertCustomTrue();
});
