<?php

declare (strict_types=1);
namespace Pest;

function version(): string
{
    return '4.4.2';
}
function test_directory(string $file = ''): string
{
    return Test_Suite::get_instance()->test_path . DIRECTORY_SEPARATOR . $file;
}