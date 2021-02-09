<?php

namespace Valantic\PimcoreFormsBundle\Exception;

class DuplicateOutputException extends BaseException
{
    public function __construct(array $names)
    {
        if (count($names) === 1) {
            parent::__construct(sprintf('Output %s is registered multiple times', $names[0]), 0, null);

            return;
        }
        parent::__construct(sprintf('Outputs %s are registered multiple times', implode(', ', $names)), 0, null);
    }
}
