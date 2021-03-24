<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Exception;

class UnknownRedirectHandlerException extends BaseException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Unknown redirect handler %s', $name), 0, null);
    }
}
