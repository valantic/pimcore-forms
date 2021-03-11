<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Exception;

use RuntimeException;
use Throwable;

abstract class BaseException extends RuntimeException
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
