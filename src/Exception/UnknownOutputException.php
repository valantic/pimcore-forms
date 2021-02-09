<?php

namespace Valantic\PimcoreFormsBundle\Exception;

class UnknownOutputException extends BaseException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Unknown output %s', $name), 0, null);
    }
}
