<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Exception;

class InvalidFormConfigException extends BaseException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('No valid config with name %s found', $name), 0, null);
    }
}
