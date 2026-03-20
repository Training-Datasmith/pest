<?php

declare (strict_types=1);
namespace Pest\Exceptions;

use Nuno_Maduro\Collision\Contracts\Renderless_Trace;
use RuntimeException;
/**
 * @internal
 */
final class Fatal_Exception extends RuntimeException implements Renderless_Trace
{
}