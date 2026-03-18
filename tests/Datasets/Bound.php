<?php

declare(strict_types=1);

dataset('bound.closure', function () {
    yield function () {
        return 1;
    };
    yield function () {
        return 2;
    };
});

dataset('bound.array', [
    function () {
        return 1;
    },
    function () {
        return 2;
    },
]);
