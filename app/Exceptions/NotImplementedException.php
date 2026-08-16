<?php

namespace App\Exceptions;

use RuntimeException;

class NotImplementedException extends RuntimeException
{
    public function __construct(string $adapter, string $method)
    {
        parent::__construct("{$adapter} does not implement {$method}() yet.");
    }
}
