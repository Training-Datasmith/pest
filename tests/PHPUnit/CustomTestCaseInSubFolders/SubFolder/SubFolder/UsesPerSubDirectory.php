<?php

declare(strict_types=1);

test('closure was bound to CustomTestCase', function () {
    $this->assertCustomInSubFolderTrue();
});
