<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Exception;

class DuplicateOutputException extends BaseException
{
    /**
     * DuplicateOutputException constructor.
     *
     * @param array<string> $names
     */
    public function __construct(array $names)
    {
        if (count($names) === 1) {
            parent::__construct(sprintf('Output %s is registered multiple times', $names[0]));

            return;
        }
        parent::__construct(sprintf('Outputs %s are registered multiple times', implode(', ', $names)));
    }
}
