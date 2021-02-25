<?php

declare(strict_types=1);

namespace Valantic\PimcoreFormsBundle\Form\Type;

interface ChoicesInterface
{
    /**
     * @return array<mixed,mixed>
     */
    public function choices(): array;
}
