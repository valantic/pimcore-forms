<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Exception\Repository;

use Valantic\PimcoreFormsBundle\Exception\BaseException;

class ItemNotFoundInRepositoryException extends BaseException
{
    public function __construct(string $key)
    {
        parent::__construct(sprintf('Item %s not found in repository', $key));
    }
}
