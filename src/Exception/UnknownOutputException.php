<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Exception;

class UnknownOutputException extends BaseException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Unknown output %s', $name));
    }
}
