<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Exception;

class UnknownInputHandlerException extends BaseException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Unknown input handler %s', $name));
    }
}
